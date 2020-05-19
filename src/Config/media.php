<?php

use Netflex\Pages\Components\Picture;

/**
 *
 */
return [
    'breakpoints' => [
        'xss' => 320,
        'xs' => 480,
        'sm' => 768,
        'md' => 992,
        'lg' => 1200,
        'xl' => 1440,
        'xxl' => 1920,
    ],

    'presets' => [
        'default' => [
            'mode' => Picture::MODE_ORIGINAL,
            'resolutions' => ['1x', '2x'],
        ]
    ],
];
