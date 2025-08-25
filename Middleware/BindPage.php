<?php

namespace Netflex\Pages\Middleware;

use Closure;

use Carbon\Carbon;
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
    $locale = null;
    $route = route_hash($request->route());

    if (App::has($route)) {
      current_page(App::get($route));

      if (!locale_is_locked()) {
        if ($page = current_page()) {
          if ($page->lang) {
            $locale = $page->lang;
          } else {
            $master = $page->master;
            if ($master && $master->lang) {
              $locale = $master->lang;
            }
          }
        }

        if ($locale) {
          App::setLocale($locale);
          Carbon::setLocale($locale);
        }
      }
    }

    return $next($request);
  }
}
