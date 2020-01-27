<?php

namespace Netflex\Pages\Middleware;

use Closure;

use Netflex\Pages\Page;
use Netflex\Routing\Route;

use Illuminate\Support\Facades\App;

class BindPage
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {
    $routeHash = spl_object_hash($request->route());
    dd($routeHash);

    if (App::has($routeHash)) {
      current_page(App::get($routeHash));
    }

    return $next($request);
  }
}
