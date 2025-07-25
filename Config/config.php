<?php
// File: Config/config.php

return [
    'name'        => 'Theme Switching by Leuchtfeuer',
    'description' => 'Allows MJML Theme switching without losing all content by introducing markers.<br><br> Theme Switching for MJML works best if your themes include the proper markers. Docs: <a href="https://leuchtfeuer.com/mautic/know-how/theme-switching-plugin" target="_blank">Documentation</a>.',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'version'     => '1.0.0',
    'license'     => 'GPL-3.0',
//    'icon'        => 'plugins/LeuchtfeuerThemeSwitchingBundle/Assets/img/LeuchtfeuerThemeSwitchingBundle.png',
// if no icon provided Mautic uses default fallback of Assets/img/icon.png


    'routes' => [
        'main' => [ // we can also use public instead of main, but that is open to anyone
            'plugin_theme_switch_save' => [
                'path'       => '/plugin/theme-switch/save',
                'controller' => 'LeuchtfeuerThemeSwitchingBundle:ThemeSwitching:save',
                'method'     => 'POST',
            ],

//// Not Working
//            'plugin_theme_switch_check_email_type' => [
//                'path'       => '/plugin/theme-switch/email-type/{id}',
//                'controller' => 'LeuchtfeuerThemeSwitchingBundle:ThemeSwitching:checkEmailType',
//                'method'     => 'GET',
//            ],

            'plugin_theme_switch_check_email_type' => [
                'path'       => '/plugin/theme-switch/email-type/{id}',
                'controller' => 'MauticPlugin\\LeuchtfeuerThemeSwitchingBundle\\Controller\\ThemeSwitchingController::checkEmailTypeAction',
                'method'     => 'GET',
            ],


        ],
    ],
    // --- Add the services definition here ---
    'services' => [
//        'integrations' => [
//            'mautic.integration.themeswitching' => [ // Unique service ID
//                'class' => MauticPlugin\LeuchtfeuerThemeSwitchingBundle\Integration\ThemeSwitchingIntegration::class,
//                'arguments' => [
//                    // These arguments are standard for Mautic integrations
//                    'event_dispatcher',
//                    'mautic.helper.cache_storage',
//                    'doctrine.orm.entity_manager',
//                    'session',
//                    'request_stack',
//                    'router',
//                    'translator',
//                    'monolog.logger.mautic',
//                    'mautic.helper.encryption',
//                    'mautic.lead.model.lead',
//                    'mautic.lead.model.company',
//                    'mautic.helper.paths',
//                    'mautic.core.model.notification',
//                    'mautic.lead.model.field',
//                    'mautic.plugin.model.integration_entity',
//                    'mautic.lead.model.dnc',
//                ],
//            ],
//        ],

    ],
    'parameters' => [],
];
