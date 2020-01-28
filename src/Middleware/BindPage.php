<?php

namespace Netflex\Pages\Middleware;

use Netflex\SDK\Application;

use Closure;

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
    $route = route_hash($request->route());

    if (App::has($route)) {
      current_page(App::get($route));
    }

    return $next($request);
  }
}
