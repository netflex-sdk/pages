<?php

namespace Netflex\Pages;

use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Illuminate\Foundation\Mix as ServiceProvider;

class Mix extends ServiceProvider
{
  /**
   * Get the path to a versioned Mix file.
   *
   * @param  string  $path
   * @param  string  $manifestDirectory
   * @return \Illuminate\Support\HtmlString|string
   *
   * @throws \Exception
   */
  public function __invoke($path, $manifestDirectory = '')
  {
    $path = parent::__invoke($path, $manifestDirectory);

    if (Str::contains($_SERVER['HTTP_REFERER'] ?? '', 'netflexapp.no')) {
      return new HtmlString(development_domain() . $path);
    }

    return $path;
  }
}
