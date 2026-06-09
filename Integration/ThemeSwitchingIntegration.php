<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ThemeSwitchingIntegration extends AbstractIntegration
{
    public function getName(): string
    {
        return 'ThemeSwitching';
    }

    /**
     * Returns the display name shown in the Mautic UI.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        return 'Theme Switching by Leuchtfeuer';
    }

    /**
     * Returns the path to the plugin's icon.
     *
     * @return string
     */
    public function getIcon(): string
    {
        return 'plugins/LeuchtfeuerThemeSwitchingBundle/Assets/img/LeuchtfeuerThemeSwitchingBundle.png';
    }

    /**
     * Defines the authentication type. 'none' is appropriate for a plugin without external auth.
     *
     * @return string
     */
    public function getAuthenticationType(): string
    {
        return 'none';
    }

    /**
     * Determines if the integration is configured and ready to be used.
     * Returning true means it can be enabled/disabled via the toggle without needing
     * specific settings first.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return true;
    }

    /**
     * Provides configuration form settings, like help text.
     *
     * @return array
     */
    public function getFormSettings(): array
    {
        return [
            'help' => [
                'header' => 'Theme Switching for MJML',
                'description' => 'Theme Switching for MJML works best if your themes include the proper markers. See <a href="https://leuchtfeuer.com/mautic/know-how/theme-switching-plugin" target="_blank">documentation</a>.'
            ],
        ];
    }

    /**
     * Adds elements to the plugin's configuration form.
     * Adds a static-like info block using a read-only textarea.
     *
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $data
     * @param \Symfony\Component\Form\FormEvent $formModifier
     */
    public function appendToForm(&$builder, $data, $formModifier): void
    {
        $builder->add(
            'plugin_info_text',
            TextareaType::class,
            [
                'label' => false,
                'data' => 'Theme Switching for MJML works best if your themes include the proper markers. Docs: https://leuchtfeuer.com/mautic/know-how/theme-switching-plugin',
                'attr' => [
                    'readonly' => true,
                    'style' => 'border: none; background-color: #f8f9fa; padding: 10px; resize: none; overflow: hidden; font-size: 0.9em; color: #6c757d;',
                    'rows' => 3,
                ],
                'mapped' => false,
                'required' => false,
            ]
        );
    }

}
