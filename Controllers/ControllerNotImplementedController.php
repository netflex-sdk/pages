<?php

namespace Netflex\Pages\Controllers;

use Netflex\Pages\Exceptions\ControllerNotImplementedException;
use Netflex\Pages\Providers\RouteServiceProvider;
use ReflectionClass;

class ControllerNotImplementedController extends Controller
{
  protected string $namespace;

  protected $routes = [
    [
      'url' => '/',
      'action' => 'index',
      'methods' => ['GET'],
      'name' => 'index',
    ],
  ];

  /** @throws ControllerNotImplementedException */
  public function index ()
  {
    $page = current_page();

    $routeServiceProvider = new RouteServiceProvider(app());
    $reflectionClass = new ReflectionClass($routeServiceProvider);
    $namespaceProp = $reflectionClass->getProperty('namespace');
    $namespaceProp->setAccessible(true);
    $namespace = $namespaceProp->getValue($routeServiceProvider);

    // We want to provide a stacktrace while in debug, but just a generic 404 when in production.
    if (app()->environment() !== 'master') {
      throw new ControllerNotImplementedException($namespace, $page->template->controller);
    } else {
      abort(404);
    }
  }
}
