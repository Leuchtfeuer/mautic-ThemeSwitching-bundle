<?php

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Service;

use Mautic\EmailBundle\Model\EmailModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

use Doctrine\ORM\EntityManagerInterface;

class ThemeSwitchingService
{
    private LoggerInterface $logger;
    private CoreParametersHelper $coreParameters;
    private EntityManagerInterface $doctrine;

    public function __construct(
        LoggerInterface $logger,
        CoreParametersHelper $coreParameters,
        EntityManagerInterface $doctrine
    ) {
        $this->logger = $logger;
        $this->coreParameters = $coreParameters;
        $this->doctrine = $doctrine;
    }

    public function mergeMjmlTemplates(string $oldHtml, string $newHtml, bool $isTranslationMode): string
    {
        $this->logger->info('[ThemeSwitchingPlugin] Hooked into mergeMjmlTemplates.');
        $this->logger->info('[ThemeSwitchingPlugin] Translation mode: ' . ($isTranslationMode ? 'yes' : 'no'));

        return <<<HTML
<mjml>
  <mj-head><mj-title>Plugin Injected</mj-title></mj-head>
  <mj-body>
    <mj-section>
      <mj-column>
        <mj-text>🎉 ThemeSwitchingService was called!</mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
HTML;
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

        if (!$email || !$originalEmail || !$originalEmail->getCustomHtml()) {
            return false;
        }

        $themePath = $this->coreParameters->get('themes_path') . '/' . $template . '/html/email.html';

        $newThemeHtml = file_exists($themePath)
            ? file_get_contents($themePath)
            : '<mjml><mj-body><mj-section><mj-column><mj-text>⚠ Theme file not found</mj-text></mj-column></mj-section></mj-body></mjml>';

        $mergedHtml = $this->mergeMjmlTemplates(
            $originalEmail->getCustomHtml(),
            $newThemeHtml,
            $translationMode
        );

        // Fetch existing MJML
        $connection = $this->doctrine->getConnection();
        $existing = $connection->fetchOne('SELECT custom_mjml FROM bundle_grapesjsbuilder WHERE email_id = ?', [$emailId]);

        // Prepend new MJML to existing content
        $combinedHtml = $mergedHtml . "\n\n<!-- Previous content below -->\n\n" . ($existing ?: '');

        // Save updated MJML back to database
        $connection->update(
            'bundle_grapesjsbuilder',
            ['custom_mjml' => $combinedHtml],
            ['email_id' => $emailId]
        );

        // Update email entity with new template
        $email->setTemplate($template);
        $model->saveEntity($email);

        return true;
    }





//    public function mergeAndSaveEmail(
//        EmailModel $model,
//                   $emailId,
//                   $originalEmailId,
//        string $template,
//        bool $translationMode = false
//    ): bool {
//        $this->logger->info('[ThemeSwitch] mergeAndSaveEmail() reached.', [
//            'emailId' => $emailId,
//            'template' => $template,
//        ]);
//
//        $email = $model->getEntity($emailId);
//        $originalEmail = $model->getEntity($originalEmailId);
//
//        if (!$email || !$originalEmail || !$originalEmail->getCustomHtml()) {
//            return false;
//        }
//
//        $themePath = $this->coreParameters->get('themes_path') . '/' . $template . '/html/email.html';
//
//        $newThemeHtml = file_exists($themePath)
//            ? file_get_contents($themePath)
//            : '<mjml><mj-body><mj-section><mj-column><mj-text>⚠ Theme file not found</mj-text></mj-column></mj-section></mj-body></mjml>';
//
//        $mergedHtml = $this->mergeMjmlTemplates($originalEmail->getCustomHtml(), $newThemeHtml, $translationMode);
//        $combinedHtml = $mergedHtml . "\n\n<!-- ORIGINAL CONTENT BELOW -->\n\n" . $email->getCustomHtml();
//
//        $email->setCustomHtml($combinedHtml);
//        $email->setTemplate($template);
//        $model->saveEntity($email);
//
//        return true;
//    }
}
