# Netflex Pages

<a href="https://packagist.org/packages/netflex/pages"><img src="https://img.shields.io/packagist/v/netflex/pages?label=stable" alt="Stable version"></a>
<a href="https://github.com/netflex-sdk/framework/actions/workflows/split_monorepo.yaml"><img src="https://github.com/netflex-sdk/framework/actions/workflows/split_monorepo.yaml/badge.svg" alt="Build status"></a>
<a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/github/license/netflex-sdk/log.svg" alt="License: MIT"></a>
<a href="https://github.com/netflex-sdk/sdk/graphs/contributors"><img src="https://img.shields.io/github/contributors/netflex-sdk/sdk.svg?color=green" alt="Contributors"></a>
<a href="https://packagist.org/packages/netflex/pages/stats"><img src="https://img.shields.io/packagist/dm/netflex/pages" alt="Downloads"></a>

[READ ONLY] Subtree split of the Netflex Pages component (see [netflex/framework](https://github.com/netflex-sdk/framework))

Eloquent compatible model for working with Netflex Pages.

<a href="https://packagist.org/packages/netflex/pages"><img src="https://img.shields.io/packagist/v/netflex/pages?label=stable" alt="Stable version"></a>
<a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/github/license/netflex-sdk/pages.svg" alt="License: MIT"></a>
<a href="https://packagist.org/packages/netflex/pages/stats"><img src="https://img.shields.io/packagist/dm/netflex/pages" alt="Downloads"></a>

## Installation

```bash
composer require netflex/pages
```

## Generating configuration files

```bash
php artisan vendor:publish --tag=config
```

## Configuring custom media presets

```php
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
        ],

        'banner' => [
          'mode' => Picture::MODE_LANDSCAPE,
          'resolutions' => ['1x', '2x'],
          'size' => [1920, 600],
          // Customize config per breakpoint:
          'breakpoints' => [
            'md' => [
              'mode' => Picture::MODE_FIT,
              'resolutions' => ['1x'].
            ],
            'lg' => 'md', // Aliasing 'lg' breakpoint to 'md'
          ]
        ],
    ],
];
```

## Example usage

```php
<?php

use Netflex\Pages\Page;

$page = Page::find(10000);

$slug = 'top-10-tricks-for-working-with-netflex';
$pageForUrl = Page::resolve($slug);

$firstPage = Page::first();
$lastPage = Page::last();

$newestPage = Page::orderBy('updated', 'desc')->first();

$freshPage = new Page([
  'name' => 'Fresh new article',
  'author' => 'John Doe',
  'content' => '<h1>Hello world!</h1>'
]);

$freshPage->save();
```

## Contributing

Thank you for considering contributing to the Netflex Pages! Please read the [contribution guide](CONTRIBUTING.md).

## Code of Conduct

In order to ensure that the community is welcoming to all, please review and abide by the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

Netflex Pages is open-sourced software licensed under the [MIT license](LICENSE.md).

<hr>

Copyright &copy; 2020 **[Apility AS](https://apility.no)**
