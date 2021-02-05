<?php

namespace Netflex\Pages\Controllers;

use Illuminate\Routing\Controller as BaseController;

use Netflex\Pages\Page;

use Netflex\Pages\Exceptions\PageNotBoundException;
use Netflex\Pages\Exceptions\InvalidRouteDefintionException;

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

    $validRouteDefinitions = $routes->reduce(function ($valid, $route) {
      $validKeys = ['url', 'method', 'methods', 'action', 'name'];
      return $valid && is_array($route) && Collection::make(array_keys($route))->reduce(function ($valid, $key) use ($validKeys, $route) {
        $validKey = in_array($key, $validKeys);
        if (!$validKey) {
          throw new InvalidRouteDefintionException(static::class, $route, InvalidRouteDefintionException::E_ILLEGAL_KEY, $key);
        }
        return $valid && $validKey;
      }, true);
    }, true);

    if (!$validRouteDefinitions) {
      throw new InvalidRouteDefintionException(static::class, null, InvalidRouteDefintionException::E_INVALID);
    }

    $hasIndexRoute = $routes->first(function ($route) {
      $methods = collect([$route['methods'] ?? [], $route['method'] ?? null])
        ->flatten()
        ->filter()
        ->unique()
        ->map(function ($method) {
          return strtoupper($method);
        });

      return $route['url'] === '/' && $methods->contains('GET');
    });

    if (!$hasIndexRoute) {
      $routes->push([
        'name' => 'index',
        'url' => '/',
        'methods' => ['GET'],
        'action' => 'fallbackIndex'
      ]);
    }

    return $routes
      ->map(function ($route) {
        $methods = collect([$route['methods'] ?? [], $route['method'] ?? null])
          ->flatten()
          ->filter()
          ->unique()
          ->map(function ($method) {
            return strtoupper($method);
          });

          if ($methods->count() && isset($route['action']) && isset($route['url'])) {
            return (object) [
              'name' => $route['name'] ?? null,
              'methods' => $methods->toArray(),
              'action' => $route['action'],
              'url' => $route['url']
            ];
          }
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
