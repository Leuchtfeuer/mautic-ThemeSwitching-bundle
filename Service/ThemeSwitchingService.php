<?php

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\LeuchtfeuerTranslationsBundle\Service\MjmlTranslateService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class ThemeSwitchingService
{
    private LoggerInterface $logger;
    private CoreParametersHelper $coreParameters;
    private EntityManagerInterface $doctrine;
    private Environment $twig;
    private ContainerInterface $container;
    private ?MjmlTranslateService $mjmlTranslator;

    public function __construct(
        LoggerInterface $logger,
        CoreParametersHelper $coreParameters,
        EntityManagerInterface $doctrine,
        Environment $twig,
        ContainerInterface $container,
        ?MjmlTranslateService $mjmlTranslator = null
    ) {
        $this->logger          = $logger;
        $this->coreParameters  = $coreParameters;
        $this->doctrine        = $doctrine;
        $this->twig            = $twig;
        $this->container       = $container;
        $this->mjmlTranslator  = $mjmlTranslator; // optional (bundle may not be installed)
    }

    public function mergeAndSaveEmail(
        EmailModel $model,
                   $emailId,
                   $originalEmailId,
        string $template,
        bool $translationMode = false
    ): bool {
        $this->logger->info('[ThemeSwitch] mergeAndSaveEmail() reached.', [
            'emailId'  => $emailId,
            'template' => $template,
            'mode'     => $translationMode ? 'translation' : 'merge',
        ]);

        $email         = $model->getEntity($emailId);
        $originalEmail = $model->getEntity($originalEmailId);
        if (!$email || !$originalEmail) {
            $this->logger->error('[ThemeSwitch] Invalid email entity.');
            return false;
        }

        $connection = $this->doctrine->getConnection();
        $tableName  = $this->getGrapesJsTableName();

        // Load MJML from Grapes table
        $originalMjml = $connection->fetchOne(
            "SELECT custom_mjml FROM {$tableName} WHERE email_id = ?",
            [$originalEmailId]
        );

        if (!$originalMjml) {
            $this->logger->error('[ThemeSwitch] Missing original MJML source.', ['originalEmailId' => $originalEmailId]);
            return false;
        }

        // Read and normalize targetLang from request (?targetLang=XX or XX-YY)
        $targetLang = '';
        try {
            if ($this->container->has('request_stack')) {
                $req = $this->container->get('request_stack')->getCurrentRequest();
                if ($req) {
                    $targetLang = $this->normalizeTargetLang((string) $req->query->get('targetLang', ''));
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('[ThemeSwitch] Could not read targetLang from request.', ['ex' => $e->getMessage()]);
        }
        $this->logger->info('[ThemeSwitch] targetLang detected', ['targetLang' => $targetLang !== '' ? $targetLang : '(none)']);

        // Pre-merge translation (only if Translation Mode + targetLang + translator available)
        if ($translationMode && $targetLang !== '') {
            if ($this->mjmlTranslator) {
                try {
                    $this->logger->info('[ThemeSwitch] Calling MjmlTranslateService.translateMjml()', ['lang' => $targetLang]);

                    $res = $this->mjmlTranslator->translateMjml($originalMjml, $targetLang);

                    if (is_array($res) && isset($res['mjml']) && is_string($res['mjml'])) {
                        $originalMjml = $res['mjml'];
                    } elseif (is_string($res)) {
                        // In case translator ever returns a plain string
                        $originalMjml = $res;
                    } else {
                        $this->logger->warning('[ThemeSwitch] Translator returned unexpected type; using original MJML.');
                    }

                } catch (\Throwable $e) {
                    $this->logger->warning('[ThemeSwitch] Translation failed; proceeding without translation', [
                        'ex'   => $e->getMessage(),
                        'lang' => $targetLang,
                    ]);
                }
            } else {
                $this->logger->info('[ThemeSwitch] MjmlTranslateService not injected; skipping translation.');
            }
        } else {
            $this->logger->info('[ThemeSwitch] Translation skipped', [
                'mode' => $translationMode ? 'on' : 'off',
                'lang' => $targetLang ?: '(none)',
            ]);
        }

        // Load theme file (twig/html) with fallback
        try {
            $newThemeHtml = $this->loadThemeMjml($template);
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('[ThemeSwitch] Invalid theme template.', [
                'template' => $template,
                'message'  => $e->getMessage(),
            ]);

            return false;
        }

        // Merge (uses markers + translationMode behavior)
        $mergedHtml = $this->mergeMjml(
            $originalMjml,
            $newThemeHtml,
            $translationMode
        );

        // Compile Twig placeholders inside MJML (e.g., asset URLs)
        $compiledHtml = $this->compileTwigMjml($mergedHtml, $template);

        // Persist MJML back to Grapes table (insert when the row does not exist yet)
        $updated = $connection->update(
            $tableName,
            ['custom_mjml' => $compiledHtml],
            ['email_id' => $emailId]
        );

        if (0 === $updated) {
            $connection->insert($tableName, [
                'email_id'    => $emailId,
                'custom_mjml' => $compiledHtml,
            ]);
        }

        // Update email template assignment (do not set customHtml; MJML is source of truth)
        $email->setTemplate($template);

        $model->saveEntity($email);

        return true;
    }

    private function extractLockedSections(string $mjml): array
    {
        preg_match_all(
            '/<!--\s*LOCKED_START\s*-->(.*?)<!--\s*LOCKED_END\s*-->/s',
            $mjml,
            $matches
        );

        return $matches[0]; // includes full LOCKED block (with comments)
    }

    private function removeLockedSections(string $mjml): string
    {
        return preg_replace('/<!--\s*LOCKED_START\s*-->(.*?)<!--\s*LOCKED_END\s*-->/s', '', $mjml);
    }

    private function replaceLockedSections(array $oldBlocks, array $newBlocks): array
    {
        $merged = [];
        $count  = max(count($oldBlocks), count($newBlocks));
        for ($i = 0; $i < $count; $i++) {
            if (isset($newBlocks[$i])) {
                $merged[] = $newBlocks[$i];
            } elseif (isset($oldBlocks[$i])) {
                $merged[] = $oldBlocks[$i];
            }
        }
        return $merged;
    }

    public function mergeMjml(string $oldMjml, string $newMjml, bool $translationMode): string
    {
        // Extract <mj-head> from new theme
        preg_match('/<mj-head>(.*?)<\/mj-head>/s', $newMjml, $newHeadMatch);
        $newHead = $newHeadMatch[0] ?? '<mj-head></mj-head>';

        // Extract full <mj-body> opening tag (with attributes) from new theme
        preg_match('/<mj-body([^>]*)>/i', $newMjml, $bodyTagMatch);
        $bodyAttributes = !empty($bodyTagMatch[1]) ? ' ' . $bodyTagMatch[1] : '';

        // Extract body content (excluding opening/closing tags)
        preg_match('/<mj-body[^>]*>(.*?)<\/mj-body>/s', $oldMjml, $oldBodyMatch);
        preg_match('/<mj-body[^>]*>(.*?)<\/mj-body>/s', $newMjml, $newBodyMatch);

        $oldBodyContent = $oldBodyMatch[1] ?? '';
        $newBodyContent = $newBodyMatch[1] ?? '';

        // Split into segments & pick locked/unlocked from both
        $oldSegments  = $this->splitIntoSegments($oldBodyContent);
        $newLocked    = $this->extractLockedSections($newBodyContent);
        $newUnlocked  = trim($this->removeLockedSections($newBodyContent));

        // Merge: replace locked blocks with theme's locked, keep unlocked, append remaining locked
        $mergedSegments = [];
        $newLockedIndex = 0;

        foreach ($oldSegments as $segment) {
            if ($segment['type'] === 'locked') {
                // Replace with new theme's LOCKED block if available
                if (isset($newLocked[$newLockedIndex])) {
                    $mergedSegments[] = $newLocked[$newLockedIndex];
                    $newLockedIndex++;
                }
                // If not, do nothing: extra old locked blocks are dropped
            } else {
                // Preserve unlocked content
                $mergedSegments[] = $segment['content'];
            }
        }



        // Append any remaining locked blocks from new theme
        while ($newLockedIndex < count($newLocked)) {
            $mergedSegments[] = $newLocked[$newLockedIndex];
            $newLockedIndex++;
        }

        // Combine segments
        $mergedBodyContent = implode("\n\n", $mergedSegments);

        // Only in Smart Merge, append theme's unlocked content
        if (!$translationMode && !empty($newUnlocked)) {
            $mergedBodyContent .= "\n\n" . $newUnlocked;
        }

        // Build final MJML with preserved <mj-body> attributes
        return "<mjml>\n{$newHead}\n<mj-body{$bodyAttributes}>{$mergedBodyContent}</mj-body>\n</mjml>";
    }

    /**
     * Render MJML/Twig placeholders into final HTML (or MJML) so images load.
     *
     * For example, transforms:
     *   <img src="{{ getAssetUrl('themes/'~template~'/assets/logo.png') }}" />
     * into something like:
     *   <img src="/plugins/SomeBundle/themes/themeName/assets/logo.png" />
     *
     * or the absolute URL, depending on how Mautic’s getAssetUrl is configured.
     */
    private function compileTwigMjml(string $rawMjml, string $themeAlias = ''): string
    {
        try {
            $templateObject = $this->twig->createTemplate($rawMjml);
            return $templateObject->render([
                'template' => $themeAlias,
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('[ThemeSwitch] Failed to compile twig placeholders: ' . $e->getMessage());
            return $rawMjml;
        }
    }

    private function splitIntoSegments(string $bodyContent): array
    {
        $pattern = '/(<!--\s*LOCKED_START\s*-->.*?<!--\s*LOCKED_END\s*-->)|((?:(?!<!--\s*LOCKED_(?:START|END)\s*-->).)+)/s';
        preg_match_all($pattern, $bodyContent, $matches, PREG_SET_ORDER);

        $segments = [];
        foreach ($matches as $match) {
            if (!empty($match[1])) {
                $segments[] = ['type' => 'locked', 'content' => $match[1]];
            } elseif (!empty($match[2])) {
                $content = trim($match[2]);
                if (!empty($content)) {
                    $segments[] = ['type' => 'unlocked', 'content' => $content];
                }
            }
        }

        return $segments;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function loadThemeMjml(string $template): string
    {
        $template = basename(str_replace('\\', '/', $template));
        if ('' === $template || str_contains($template, '..')) {
            throw new \InvalidArgumentException('Invalid theme name.');
        }

        $themesPath = $this->coreParameters->get('themes_path');
        if (!$themesPath) {
            $themesPath = realpath(__DIR__ . '/../../../themes');
        }

        $themesRealPath = $themesPath ? realpath($themesPath) : false;
        if (false === $themesRealPath || !is_dir($themesRealPath)) {
            throw new \InvalidArgumentException('Themes directory not found.');
        }

        $themeDir = realpath($themesRealPath.DIRECTORY_SEPARATOR.$template);
        if (false === $themeDir || !str_starts_with($themeDir, $themesRealPath.DIRECTORY_SEPARATOR)) {
            throw new \InvalidArgumentException('Theme directory not found.');
        }

        $htmlDir = $themeDir.DIRECTORY_SEPARATOR.'html';
        $twigPath = $htmlDir.DIRECTORY_SEPARATOR.'email.html.twig';
        $htmlPath = $htmlDir.DIRECTORY_SEPARATOR.'email.html';

        if (file_exists($twigPath)) {
            $this->logger->info('[ThemeSwitch] Found MJML theme file (.twig)', ['path' => $twigPath]);

            return (string) file_get_contents($twigPath);
        }

        if (file_exists($htmlPath)) {
            $this->logger->info('[ThemeSwitch] Found MJML theme file (.html)', ['path' => $htmlPath]);

            return (string) file_get_contents($htmlPath);
        }

        $this->logger->error('[ThemeSwitch] MJML theme file not found', [
            'checkedTwigPath' => $twigPath,
            'checkedHtmlPath' => $htmlPath,
        ]);

        return '<mjml><mj-body><mj-section><mj-column><mj-text>⚠ Theme file not found</mj-text></mj-column></mj-section></mj-body></mjml>';
    }

    /**
     * Normalize language strings to what DeepL typically expects (e.g. EN, DE, EN-GB, PT-BR).
     */
    private function getGrapesJsTableName(): string
    {
        return (string) $this->coreParameters->get('db_table_prefix', '').'bundle_grapesjsbuilder';
    }

    private function normalizeTargetLang(?string $raw): string
    {
        if (!$raw) {
            return '';
        }
        $l = strtoupper(trim($raw));

        // Common alias fixes
        $map = [
            'EN_UK' => 'EN-GB',
            'EN-UK' => 'EN-GB',
            'EN_GB' => 'EN-GB',
            'EN_US' => 'EN-US',
            'PT_BR' => 'PT-BR',
            'PT_PT' => 'PT-PT',
        ];
        if (isset($map[$l])) {
            return $map[$l];
        }

        // If it's already a two-letter or XX-YY form, pass through as-is
        return $l;
    }
}
