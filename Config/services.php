<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration\Support\ConfigSupport;
use MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration\ThemeSwitchingIntegration;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('MauticPlugin\\LeuchtfeuerThemeSwitchingBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');

    $services->get(ThemeSwitchingIntegration::class)
        ->tag('mautic.integration')
        ->tag('mautic.basic_integration');

    $services->get(ConfigSupport::class)
        ->tag('mautic.config_integration');

    $services->alias('mautic.integration.themeswitching', ThemeSwitchingIntegration::class)
        ->public();
};
