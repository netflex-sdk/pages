<?php

namespace Netflex\Pages\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
  /**
   * Additional Netflex page routes
   *
   * @var array
   */
  protected $routes = [];

  public function getRoutes()
  {
    return collect($this->routes)
      ->map(function ($route) {
        return (object) [
          'methods' => $route['methods'] ?? ['GET'],
          'action' => $route['action'] ?? 'index',
          'url' => $route['url'] ?? '/'
        ];
      });
  }
}
