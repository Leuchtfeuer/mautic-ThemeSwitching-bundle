<?php

declare(strict_types=1);

return [
    'name'        => 'Theme Switching by Leuchtfeuer',
    'description' => 'Allows MJML Theme switching without losing all content by introducing markers.<br><br> Theme Switching for MJML works best if your themes include the proper markers.',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'version'     => '7.0.0',
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
    'parameters' => [],
];
