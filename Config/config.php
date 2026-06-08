<?php

return [
    'name'        => 'Theme Switching by Leuchtfeuer',
    'description' => 'Allows MJML Theme switching without losing all content by introducing markers.<br><br> Theme Switching for MJML works best if your themes include the proper markers. Docs: <a href="https://leuchtfeuer.com/mautic/know-how/theme-switching-plugin" target="_blank">Documentation</a>.',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'version'     => '5.0.1',
    'license'     => 'GPL-3.0',

    'routes' => [
        'main' => [
            'plugin_theme_switch_merge' => [
                'path'       => '/plugin/theme-switch/merge/{emailId}',
                'controller' => 'MauticPlugin\\LeuchtfeuerThemeSwitchingBundle\\Controller\\ThemeSwitchingController::mergeAction',
                'method'     => 'POST',
            ],
            'plugin_theme_switch_check_email_type' => [
                'path'       => '/plugin/theme-switch/email-type/{id}',
                'controller' => 'MauticPlugin\\LeuchtfeuerThemeSwitchingBundle\\Controller\\ThemeSwitchingController::checkEmailTypeAction',
                'method'     => 'GET',
            ],
            'plugin_theme_switch_can_translate' => [
                'path'       => '/plugin/theme-switch/can-translate',
                'controller' => 'MauticPlugin\\LeuchtfeuerThemeSwitchingBundle\\Controller\\ThemeSwitchingController::canTranslateAction',
                'method'     => 'GET',
            ],
        ],
    ],
    'services' => [
        'integrations' => [
            'mautic.integration.themeswitching' => [
                'class'     => MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration\ThemeSwitchingIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'monolog.logger.mautic',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
    ],
    'parameters' => [],
];
