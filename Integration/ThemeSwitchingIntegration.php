<?php
// File: Integration/ThemeSwitchingIntegration.php

namespace MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Mautic\PluginBundle\Integration\AbstractIntegration;

class ThemeSwitchingIntegration extends AbstractIntegration
{
    /**
     * Returns the name of the integration, used as the integration's unique identifier.
     * Should match the service ID suffix (after `mautic.integration.`) and the tag in services.yaml.
     *
     * @return string
     */
    public function getName(): string
    {
        // Keep as 'ThemeSwitching' to match services.yaml tag and avoid 404 error.
        return 'ThemeSwitching'; // Matches service ID suffix    //possibly could be LeuchtfeuerThemeSwitchingBundle, but when I use it I get error 404 when clicking on plugin page
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
        // Use the same path as defined in config.php
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
        return true; // Allow enabling/disabling via the toggle
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
        // --- Add a read-only textarea for static information ---
        $builder->add(
            'plugin_info_text', // Unique field name
            // Use the FQCN for the form type
            TextareaType::class,
            [
                'label' => false, // Hide the standard label
                // Fixed URL (removed trailing spaces)
                'data' => 'Theme Switching for MJML works best if your themes include the proper markers. Docs: https://leuchtfeuer.com/mautic/know-how/theme-switching-plugin',
                'attr' => [
                    'readonly' => true, // Make it non-editable
                    'style' => 'border: none; background-color: #f8f9fa; padding: 10px; resize: none; overflow: hidden; font-size: 0.9em; color: #6c757d;', // Style it like static text/info
                    'rows' => 3, // Adjust rows as needed for your text
                ],
                'mapped' => false, // Important: Don't map this to an entity property
                'required' => false,
            ]
        );
    }

}
