<?php

namespace Netflex\Pages\Controllers;

use App\Article;

use Netflex\Pages\Page;
use Netflex\Pages\Exceptions\PageNotBoundException;
use Netflex\Pages\Controllers\Controller as BaseController;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;

class PageController extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

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

  public function showArticle (Article $article) {
    dd($article->id, $article->name);
  }
}
