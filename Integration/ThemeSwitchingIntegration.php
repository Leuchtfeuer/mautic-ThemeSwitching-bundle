<?php

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

class ThemeSwitchingIntegration extends AbstractIntegration
{

    public function getName(): string
    {
        return 'LeuchtfeuerThemeSwitchingBundle';
    }


    public function getDisplayName(): string
    {
        return 'Theme Switching by Leuchtfeuer';
    }

    public function getIcon(): string
    {
        return 'plugins/LeuchtfeuerThemeSwitchingBundle/Assets/img/LeuchtfeuerThemeSwitchingBundle.png';
    }


    public function getAuthenticationType(): string
    {
        return 'none';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function getFormSettings(): array
    {
        return [
            'help' => [
                'header' => 'Theme Switching for MJML',
                'description' => 'Theme Switching for MJML works best if your themes include the proper markers. See <a href="https://leuchtfeuer.com/mautic/know-how/theme-switching-plugin" target="_blank">documentation</a>.'
            ],
        ];
    }

    public function appendToForm(&$builder, $data, $formModifier): void
    {
        $logoPath = $this->getIcon(); // relative path

        $builder->add(
            'plugin_info_header',
            'static',
            [
                'label' => false,
                'attr' => [
                    'html' =>
                        '<div style="margin-bottom: 20px;">' .
                        '<img src="/' . ltrim($logoPath, '/') . '" alt="Leuchtfeuer Logo" style="max-width: 100px;" />' .
                        '<p>Theme Switching for MJML works best if your themes include the proper markers. <a href="https://leuchtfeuer.com/mautic/know-how/theme-switching-plugin" target="_blank">Docs</a>.</p>' .
                        '</div><hr>',
                    'only_form' => true,
                ],
                'mapped' => false,
                'required' => false,
            ]
        );
    }

}
