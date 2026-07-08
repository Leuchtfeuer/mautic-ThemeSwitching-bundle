<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailAssetsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private IntegrationHelper $integrationHelper,
        private Config $config,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_ASSETS => ['injectAssets', 0],
        ];
    }

    public function injectAssets(CustomAssetsEvent $event): void
    {
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

        $event->addScript(
            'plugins/LeuchtfeuerThemeSwitchingBundle/Assets/js/theme-switch.js',
            'footer',
            false,
            'leuchtfeuer_theme_switch'
        );
    }
}
