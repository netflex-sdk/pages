# Netflex Pages

Eloquent compatible model for working with Netflex Pagess.

<a href="https://packagist.org/packages/netflex/pages"><img src="https://img.shields.io/packagist/v/netflex/pages?label=stable" alt="Stable version"></a>
<a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/github/license/netflex-sdk/pages.svg" alt="License: MIT"></a>
<a href="https://packagist.org/packages/netflex/pages/stats"><img src="https://img.shields.io/packagist/dm/netflex/pages" alt="Downloads"></a>

## Installation

```bash
composer require netflex/pages
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

Thank you for considering contributing to the Netflex Structure! Please read the [contribution guide](CONTRIBUTING.md).

## Code of Conduct

In order to ensure that the community is welcoming to all, please review and abide by the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

Netflex Structure is open-sourced software licensed under the [MIT license](LICENSE.md).

<hr>

Copyright &copy; 2020 **[Apility AS](https://apility.no)**
