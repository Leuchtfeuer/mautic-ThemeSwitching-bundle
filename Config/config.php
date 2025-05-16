<?php

return [
    'name'        => 'Theme Switching by Leuchtfeuer',
    'description' => 'Allows MJML Theme switching without losing all content by introducing markers',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'version'     => '1.0.0',
    'license'     => 'GPL-3.0',
    'iconClass' => 'fa fa-retweet', // or any FA icon
    'icon' => 'plugins/LeuchtfeuerThemeSwitchingBundle/Assets/img/LeuchtfeuerThemeSwitchingBundle.png',


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


    'services' => [], // Services are defined in YAML

    'parameters' => [],
];
