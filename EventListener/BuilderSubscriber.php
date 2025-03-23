<?php

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\EventListener;

use Mautic\CoreBundle\Event\BuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Factory\MauticFactory;

class BuilderSubscriber implements EventSubscriberInterface
{
    private MauticFactory $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
//            CoreEvents::BUILDER_ON_LOAD => ['onBuilderLoad', 0],
        ];
    }

    public function onBuilderLoad(BuilderEvent $event): void
    {
        $this->factory->getAssetHelper()->addJs('plugins/LeuchtfeuerThemeSwitchingBundle/Assets/js/theme-switch.js');
    }
}
