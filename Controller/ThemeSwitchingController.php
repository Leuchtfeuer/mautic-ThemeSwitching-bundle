<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Service\ThemeSwitchingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ThemeSwitchingController extends CommonController
{
    public function mergeAction(Request $request, ThemeSwitchingService $themeSwitcher): RedirectResponse
    {
        $emailId         = (int) ($request->attributes->get('emailId') ?? $request->query->get('emailId'));
        $originalEmailId = (int) $request->query->get('original', $emailId);
        $template        = InputHelper::clean($request->query->get('template'));
        $translationMode = $request->query->getBoolean('translationMode', false);

        if ($emailId <= 0 || '' === $template) {
            $this->addFlashMessage('Theme switch failed: missing email or template.', [], 'error');

            return $this->redirectToRoute('mautic_email_index');
        }

        /** @var EmailModel $model */
        $model = $this->getModel('email');
        $email = $model->getEntity($emailId);

        if (null === $email || !$this->security->hasEntityAccess(
            'email:emails:editown',
            'email:emails:editother',
            $email->getCreatedBy()
        )) {
            return $this->accessDenied();
        }

        try {
            $result = $themeSwitcher->mergeAndSaveEmail(
                $model,
                $emailId,
                $originalEmailId,
                $template,
                $translationMode
            );
        } catch (\Throwable $e) {
            $this->logger->error('[ThemeSwitch] mergeAction failed: '.$e->getMessage());
            $result = false;
        }

        if ($result) {
            $this->addFlashMessage('Theme content merged successfully.', [], 'notice');
        } else {
            $this->addFlashMessage('Failed to merge theme content. Check logs for details.', [], 'error');
        }

        return $this->redirectToRoute('mautic_email_action', [
            'objectAction' => 'edit',
            'objectId'     => $emailId,
        ]);
    }

    public function checkEmailTypeAction(int $id): JsonResponse
    {
        /** @var EmailModel $model */
        $model = $this->getModel('email');
        $email = $model->getEntity($id);

        if (!$email) {
            return new JsonResponse(['error' => 'Email not found.'], 404);
        }

        return new JsonResponse([
            'template'   => $email->getTemplate(),
            'isCodemode' => 'mautic_code_mode' === $email->getTemplate(),
        ]);
    }

    public function canTranslateAction(): JsonResponse
    {
        $available = class_exists(\MauticPlugin\LeuchtfeuerTranslationsBundle\Service\MjmlTranslateService::class);

        return new JsonResponse([
            'available' => $available,
        ]);
    }
}
