<?php

namespace Netflex\Pages\Controllers;

use Netflex\Pages\Page;
use Illuminate\Http\Request;
use Netflex\Pages\Controllers\Controller as BaseController;
use Netflex\Pages\Exceptions\PageNotBoundException;

class PageController extends BaseController
{
  /**
   * Additional Netflex page routes
   *
   * @var array
   */
  protected $routes = [
    [
      'methods' => ['GET'],
      'action' => 'index',
      'url' => '/'
    ]
  ];

  /**
   * @param Request $request
   * @return Response
   */
  public function index(Request $request)
  {
    if ($page = Page::current()) {
      return $page->toResponse($request);
    }

    throw new PageNotBoundException;
  }
}
