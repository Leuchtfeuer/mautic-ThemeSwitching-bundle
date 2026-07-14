<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailAssetsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Config $config,
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

        if (null === $request) {
            return;
        }

        if (!$this->config->isPublished()) {
            return;
        }

        $route = $this->getAjaxRoute($request) ?? $this->getRoute($request);
        if ('mautic_email_action' !== $route) {
            return;
        }

        if (!in_array($this->getObjectAction($request), ['edit', 'new', 'clone'], true)) {
            return;
        }

        $event->addContent('<div id="lf-theme-switch-ready" style="display:none"></div>');
    }

    private function getRoute(Request $request): ?string
    {
        $route = $request->attributes->get('_route');

        return is_string($route) ? $route : null;
    }

    private function getAjaxRoute(Request $request): ?string
    {
        $ajaxRoute = $request->attributes->get('ajaxRoute');
        $route = is_array($ajaxRoute) ? ($ajaxRoute['_route'] ?? null) : null;

        return is_string($route) ? $route : null;
    }

    private function getObjectAction(Request $request): ?string
    {
        $ajaxRoute = $request->attributes->get('ajaxRoute');
        $routeParams = is_array($ajaxRoute) ? ($ajaxRoute['_route_params'] ?? null) : null;
        $objectAction = is_array($routeParams) ? ($routeParams['objectAction'] ?? null) : null;

        if (is_string($objectAction)) {
            return $objectAction;
        }

        $objectAction = $request->attributes->get('objectAction');

        return is_string($objectAction) ? $objectAction : null;
    }
}
