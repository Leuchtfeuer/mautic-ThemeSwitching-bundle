<?php

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class ThemeSwitching extends AbstractIntegration
{
    public function getName(): string
    {
        return 'Theme Switching by Leuchtfeuer';
    }

    public function getDisplayName(): string
    {
        return 'Theme Switching by Leuchtfeuer';
    }

    public function getAuthenticationType(): string
    {
        return '';
    }
}
