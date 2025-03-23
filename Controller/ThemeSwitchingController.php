<?php

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Service\ThemeSwitchingService;

class ThemeSwitchingController extends CommonController
{
    private ThemeSwitchingService $themeSwitcher;

    public function __construct(ThemeSwitchingService $themeSwitcher)
    {
        $this->themeSwitcher = $themeSwitcher;
    }

    public function saveAction(Request $request): JsonResponse
    {
        $this->logger->info('[ThemeSwitch] saveAction was triggered');

        $data = json_decode($request->getContent(), true);
        $this->logger->info('[ThemeSwitch] Request data received', $data ?? []);

        $emailId         = $data['emailId'] ?? null;
        $template        = $data['template'] ?? null;
        $originalEmailId = $data['original'] ?? $emailId;

        if (!$emailId || !$template || !$originalEmailId) {
            $this->logger->error('[ThemeSwitch] Missing parameters', [
                'emailId' => $emailId,
                'template' => $template,
                'originalEmailId' => $originalEmailId,
            ]);

            return new JsonResponse([
                'success' => false,
                'error'   => 'Missing parameters.',
                'step'    => 'validation'
            ], 400);
        }

        $model = $this->getModel('email');
        $email = $model->getEntity($emailId);
        $originalEmail = $model->getEntity($originalEmailId);

        if (!$email || !$originalEmail || !$originalEmail->getCustomHtml()) {
            $this->logger->error('[ThemeSwitch] Invalid email or missing original HTML', [
                'emailId' => $emailId,
                'originalEmailId' => $originalEmailId,
            ]);

            return new JsonResponse([
                'success' => false,
                'error'   => 'Invalid email IDs or missing HTML.',
                'step'    => 'email-lookup'
            ], 400);
        }

        $themePath = $this->get('mautic.helper.core_parameters')->get('themes_path') . '/' . $template . '/html/email.html';
        $this->logger->info('[ThemeSwitch] Theme path resolved', ['path' => $themePath]);

        $newThemeHtml = file_exists($themePath)
            ? file_get_contents($themePath)
            : '<mjml><mj-body><mj-section><mj-column><mj-text>⚠ Theme file not found</mj-text></mj-column></mj-section></mj-body></mjml>';

        $this->logger->info('[ThemeSwitch] Merging MJML templates...');
        $mergedHtml = $this->themeSwitcher->mergeMjmlTemplates(
            $originalEmail->getCustomHtml(),
            $newThemeHtml,
            false
        );

        $combinedHtml = $mergedHtml . "\n\n<!-- ORIGINAL CONTENT BELOW -->\n\n" . $email->getCustomHtml();
        $email->setCustomHtml($combinedHtml);
        $email->setTemplate($template);

        $this->logger->info('[ThemeSwitch] Saving updated email entity...', [
            'emailId'  => $emailId,
            'template' => $template,
        ]);
        $model->saveEntity($email);

        return new JsonResponse([
            'success' => true,
            'step'    => 'saved',
        ]);
    }
}
