<?php


namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Service\ThemeSwitchingService;

class ThemeSwitchingController extends CommonController
{
    private ThemeSwitchingService $themeSwitcher;
    protected  MauticFactory $factory;

    public function __construct(ThemeSwitchingService $themeSwitcher, MauticFactory $factory)
    {
        $this->themeSwitcher = $themeSwitcher;
        $this->factory = $factory;
    }

    public function saveAction(Request $request): JsonResponse
    {
        $this->logger->info('[ThemeSwitch] saveAction was triggered');

        $data = json_decode($request->getContent(), true);
        $this->logger->info('[ThemeSwitch] Request data received', $data ?? []);

        $emailId         = $data['emailId'] ?? null;
        $template        = $data['template'] ?? null;
        $originalEmailId = $data['original'] ?? $emailId;
        $translationMode = filter_var($data['translationMode'] ?? false, FILTER_VALIDATE_BOOLEAN);


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

        $model = $this->factory->getModel('email');
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

        $themePath = $this->factory->getHelper('core_parameters')->get('themes_path') . '/' . $template . '/html/email.html';
        $this->logger->info('[ThemeSwitch] Theme path resolved', ['path' => $themePath]);

        $newThemeHtml = file_exists($themePath)
            ? file_get_contents($themePath)
            : '<mjml><mj-body><mj-section><mj-column><mj-text>⚠ Theme file not found</mj-text></mj-column></mj-section></mj-body></mjml>';

        if (
            !$this->themeSwitcher->isMjmlContent($originalEmail->getCustomHtml()) ||
            !$this->themeSwitcher->isMjmlContent($newThemeHtml)
        ) {
            $this->logger->warning('[ThemeSwitch] Detected non-MJML content, falling back to default behavior.', [
                'emailId' => $emailId,
                'template' => $template,
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => 'Non-MJML content detected.',
                'step' => 'non-mjml-fallback'
            ], 400);
        }

        $this->logger->info('[ThemeSwitch] Merging MJML templates...');
        $mergedHtml = $this->themeSwitcher->mergeMjml(
            $originalEmail->getCustomHtml(),
            $newThemeHtml,
            $translationMode
        );


//        $combinedHtml = $mergedHtml . "\n\n<!-- ORIGINAL CONTENT BELOW -->\n\n" . $email->getCustomHtml();
//        $email->setCustomHtml($combinedHtml);

        $email->setCustomHtml($mergedHtml);


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

    public function checkEmailTypeAction($id): JsonResponse
    {
        $model = $this->factory->getModel('email');
        $email = $model->getEntity($id);

        if (!$email) {
            return new JsonResponse(['error' => 'Email not found.'], 404);
        }

        return new JsonResponse([
            'template' => $email->getTemplate(),
            'isCodemode' => $email->getTemplate() === 'mautic_code_mode',
        ]);
    }
}



