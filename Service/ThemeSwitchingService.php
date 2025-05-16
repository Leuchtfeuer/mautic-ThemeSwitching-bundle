<?php

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Service;

use Mautic\EmailBundle\Model\EmailModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

use Doctrine\ORM\EntityManagerInterface;

use Twig\Environment;

class ThemeSwitchingService
{
    private LoggerInterface $logger;
    private CoreParametersHelper $coreParameters;
    private EntityManagerInterface $doctrine;

    private Environment $twig;

    public function __construct(
        LoggerInterface $logger,
        CoreParametersHelper $coreParameters,
        EntityManagerInterface $doctrine,
        Environment $twig
    ) {
        $this->logger = $logger;
        $this->coreParameters = $coreParameters;
        $this->doctrine = $doctrine;
        $this->twig = $twig;
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
        ]);

        $email = $model->getEntity($emailId);
        $originalEmail = $model->getEntity($originalEmailId);

        if (!$email || !$originalEmail) {
            $this->logger->error('[ThemeSwitch] Invalid email entity.');
            return false;
        }

        $connection = $this->doctrine->getConnection();

        // Get original MJML (base) and current MJML (to be updated)
        $originalMjml = $connection->fetchOne('SELECT custom_mjml FROM bundle_grapesjsbuilder WHERE email_id = ?', [$originalEmailId]);
        $existingMjml = $connection->fetchOne('SELECT custom_mjml FROM bundle_grapesjsbuilder WHERE email_id = ?', [$emailId]);

        if (!$originalMjml) {
            $this->logger->error('[ThemeSwitch] Missing original MJML source.', ['originalEmailId' => $originalEmailId]);
            return false;
        }

        // Get theme path with fallback
        $themesPath = $this->coreParameters->get('themes_path') ?: '/var/www/html/themes';
        $basePath = rtrim($themesPath, '/') . '/' . $template . '/html/';
        $this->logger->info('[ThemeSwitch] Checking theme files in path:', ['basePath' => $basePath]);

        // Try to list contents of the directory
        if (is_dir($basePath)) {
            $filesInDir = scandir($basePath);
            $this->logger->info('[ThemeSwitch] Files found in theme html directory:', ['files' => $filesInDir]);
        } else {
            $this->logger->error('[ThemeSwitch] Theme html directory does not exist or is not a directory', ['basePath' => $basePath]);
        }

        // Look for theme file
        $twigPath = $basePath . 'email.html.twig';
        $htmlPath = $basePath . 'email.html';

        if (file_exists($twigPath)) {
            $themePathUsed = $twigPath;
            $newThemeHtml = file_get_contents($twigPath);
            $this->logger->info('[ThemeSwitch] Found MJML theme file (.twig)', ['path' => $twigPath]);
        } elseif (file_exists($htmlPath)) {
            $themePathUsed = $htmlPath;
            $newThemeHtml = file_get_contents($htmlPath);
            $this->logger->info('[ThemeSwitch] Found MJML theme file (.html)', ['path' => $htmlPath]);
        } else {
            $themePathUsed = null;
            $newThemeHtml = '<mjml><mj-body><mj-section><mj-column><mj-text>⚠ Theme file not found</mj-text></mj-column></mj-section></mj-body></mjml>';
            $this->logger->error('[ThemeSwitch] MJML theme file not found in either .twig or .html format', [
                'checkedTwigPath' => $twigPath,
                'checkedHtmlPath' => $htmlPath,
            ]);
        }


        // Merge MJML using correct source
        $mergedHtml = $this->mergeMjml(
            $originalMjml,
            $newThemeHtml,
            $translationMode
        );

        // 1) Re‐parse Twig placeholders so images become real URLs:
        $compiledHtml = $this->compileTwigMjml($mergedHtml, $template);

        // 2) Save final compiled MJML (no placeholders!)
        $connection->update(
            'bundle_grapesjsbuilder',
            ['custom_mjml' => $compiledHtml],
            ['email_id' => $emailId]
        );



        // Update email template assignment
        $email->setTemplate($template);

        ###############REMOVE THIS LINE
//        $email->setCustomHtml($compiledHtml);

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

        $count = max(count($oldBlocks), count($newBlocks));
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
        // Extract <mj-head> from new MJML theme
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

        // Extract locked and unlocked blocks
        $oldSegments = $this->splitIntoSegments($oldBodyContent);
        $newLocked = $this->extractLockedSections($newBodyContent);
        $newUnlocked = trim($this->removeLockedSections($newBodyContent));

        // Merge segments (locked and unlocked)
        $mergedSegments = [];
        $newLockedIndex = 0;

        foreach ($oldSegments as $segment) {
            if ($segment['type'] === 'locked') {
                // Replace with new theme's LOCKED block if available
                if (isset($newLocked[$newLockedIndex])) {
                    $mergedSegments[] = $newLocked[$newLockedIndex];
                    $newLockedIndex++;
                } else {
                    // Keep original if no corresponding new LOCKED
                    $mergedSegments[] = $segment['content'];
                }
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

        // If not translation mode, append theme's unlocked content at end
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
            $this->logger->warning('[ThemeSwitch] Failed to compile twig placeholders: '.$e->getMessage());
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
     * Check if a given string looks like MJML content.
     */
    public function isMjmlContent(string $html): bool
    {
        return stripos($html, '<mjml') !== false && stripos($html, '<mj-body') !== false;
    }


}
