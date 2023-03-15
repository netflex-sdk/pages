<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global settings
    |--------------------------------------------------------------------------
    |
    |
    |
    |*/
    'options' => [
        'image' => [
            'setWidthAndHeightAttributes' => false,
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Breakpoints
    |--------------------------------------------------------------------------
    |
    | Your application's defined breakpoints sizes (max-width)
    |
    */
    'breakpoints' => [
        'xss' => 320,
        'xs' => 480,
        'sm' => 768,
        'md' => 992,
        'lg' => 1200,
        'xl' => 1440,
        'xxl' => 1920,
    ],

    /*
    |--------------------------------------------------------------------------
    | Presets
    |--------------------------------------------------------------------------
    |
    | Defined media presets for responsive pictures and background-images
    | Supported parameters:
    |   - mode
    |   - resolutions
    |   - fill
    |   - size
    |   - compressor
    |   - breakpoints
    |     - mode
    |     - resolutions
    |     - fill
    |     - size
    |     - compressor
    */
    'presets' => [
        'default' => MEDIA_PRESET_ORIGINAL
    ],
];
