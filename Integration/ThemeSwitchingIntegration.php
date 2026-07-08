<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class ThemeSwitchingIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const NAME = 'ThemeSwitching';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Theme Switching by Leuchtfeuer';
    }

    public function getIcon(): string
    {
        return 'plugins/LeuchtfeuerThemeSwitchingBundle/Assets/img/LeuchtfeuerThemeSwitchingBundle.png';
    }
}
