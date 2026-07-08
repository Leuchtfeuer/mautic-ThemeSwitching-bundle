<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration\ThemeSwitchingIntegration;

class ConfigSupport extends ThemeSwitchingIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
