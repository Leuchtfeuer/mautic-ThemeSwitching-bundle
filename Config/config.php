<?php

return [
    'name'        => 'Theme Switching by Leuchtfeuer',
    'description' => 'Allows MJML Theme switching without losing all content by introducing markers',
    'author'      => 'Leuchtfeuer Digital Marketing GmbH',
    'version'     => '1.0.0',
    'license'     => 'GPL-3.0',

    'routes' => [
        'plugin' => [ // This means it's registered under /plugin/
            'plugin_theme_switch_save' => [
                'path'       => '/plugin/theme-switch/save',
                'controller' => 'LeuchtfeuerThemeSwitchingBundle:ThemeSwitching:save',
                'method'     => 'POST',
            ],
        ],
    ],


    'services' => [], // Services are defined in YAML

    'parameters' => [],
];
