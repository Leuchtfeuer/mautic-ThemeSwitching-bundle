<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailAssetsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private Config $config,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['injectMarker', 0],
        ];
    }

    public function injectMarker(CustomContentEvent $event): void
    {
        if ('email.tabs' !== $event->getContext()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request || 'mautic_email_action' !== $request->attributes->get('_route')) {
            return;
        }

        $objectAction = $request->attributes->get('objectAction');
        if (!in_array($objectAction, ['edit', 'new', 'clone'], true)) {
            return;
        }

        if (!$this->config->isPublished()) {
            return;
        }

        $event->addContent('<div id="lf-theme-switch-ready" style="display:none"></div>');
    }
}
