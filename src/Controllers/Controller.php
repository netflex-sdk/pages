<?php

namespace Netflex\Pages\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

use Netflex\Pages\Page;
use Netflex\Pages\Exceptions\PageNotBoundException;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Collection;

abstract class Controller extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

  /**
   * Additional Netflex page routes
   *
   * @var array
   */
  protected $routes = [];

  public function getRoutes()
  {
    $routes = Collection::make($this->routes);

    $hasIndexRoute = $routes->first(function ($route) {
      return $route['url'] === '/' && in_array('GET', $route['methods']);
    });

    if (!$hasIndexRoute) {
      $routes->push([
        'url' => '/',
        'methods' => ['GET'],
        'action' => 'fallbackIndex'
      ]);
    }

    return $routes
      ->map(function ($route) {
        return (object) [
          'methods' => $route['methods'] ?? ['GET'],
          'action' => $route['action'] ?? 'index',
          'url' => $route['url'] ?? '/'
        ];
      });
  }

  /**
   * @return Response
   */
  public function fallbackIndex()
  {
    if ($page = Page::current()) {
      return $page->toResponse(app('request'));
    }

    throw new PageNotBoundException;
  }
}
