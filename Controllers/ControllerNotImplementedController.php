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

    throw new ControllerNotImplementedException($namespace, $page->template->controller);
  }
}
