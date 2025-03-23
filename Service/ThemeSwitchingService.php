<?php

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Service;

use Mautic\EmailBundle\Model\EmailModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class ThemeSwitchingService
{
    private LoggerInterface $logger;
    private CoreParametersHelper $coreParameters;

    public function __construct(LoggerInterface $logger, CoreParametersHelper $coreParameters)
    {
        $this->logger         = $logger;
        $this->coreParameters = $coreParameters;
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
            'emailId' => $emailId,
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

        $mergedHtml = $this->mergeMjmlTemplates($originalEmail->getCustomHtml(), $newThemeHtml, $translationMode);
        $combinedHtml = $mergedHtml . "\n\n<!-- ORIGINAL CONTENT BELOW -->\n\n" . $email->getCustomHtml();

        $email->setCustomHtml($combinedHtml);
        $email->setTemplate($template);
        $model->saveEntity($email);

        return true;
    }
}
