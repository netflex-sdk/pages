<?php

namespace Netflex\Pages\Providers;

use Throwable;

use Netflex\Pages\Page;
use Netflex\Pages\Controllers\Controller;
use Netflex\Pages\Controllers\PageController;

use Netflex\Foundation\Redirect;

use Netflex\Pages\Middleware\BindPage;
use Netflex\Pages\Middleware\GroupAuthentication;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Log;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Collection;

class PagesServiceProvider extends ServiceProvider
{
  /**
   * @return void
   */
  public function register()
  {
    parent::register();
    $this->registerBladeDirectives();
  }

  public function boot()
  {
    $this->registerMiddlewareGroups();
    parent::boot();
  }

  protected function registerBladeDirectives()
  {
    Blade::directive('editable', function ($expression) {
      return "<?php page_editable_push($expression); ?>";
    });

    Blade::directive('editButton', function ($expression) {
      return "<?php echo edit_button($expression); ?>";
    });

    Blade::directive('content', function ($expression) {
      return "<?php echo content($expression); ?>";
    });

    Blade::directive('blocks', function ($expression) {
      return "<?php echo blocks($expression); ?>";
    });

    Blade::directive('inlineText', function ($expression) {
      return "<?php echo inline_text($expression); ?>";
    });

    Blade::directive('inlinePicture', function ($expression) {
      return "<?php echo inline_picture($expression); ?>";
    });

    Blade::directive('image', function ($expression) {
      return "<?php echo image($expression); ?>";
    });

    Blade::directive('picture', function ($expression) {
      return "<?php echo picture($expression); ?>";
    });
  }

  protected function registerMiddlewareGroups()
  {
    $router = $this->app->make('router');

    $router->aliasMiddleware('bind_page', BindPage::class);
    $router->aliasMiddleware('group_auth', GroupAuthentication::class);

    $router->middlewareGroup('netflex', [
      'web',
      'bind_page',
      'group_auth'
    ]);
  }

  protected function mapRedirects()
  {
    Collection::make(Redirect::all())
      ->each(function ($redirect) {
        Route::redirect(
          $redirect->source_url,
          $redirect->target_url,
          $redirect->type
        );
      });
  }

  protected function mapNetflexRoutes()
  {
    Route::middleware('netflex')
      ->group(function () {
        Page::all()->filter(function ($page) {
          return $page->type === 'page' && $page->template;
        })->each(function ($page) {
          $controller = $page->template->controller ?? null;
          $pageController = PageController::class;
          $class = trim($controller ? ("\\{$this->namespace}\\{$controller}") : "\\{$pageController}", '\\');

          try {
            tap(new $class, function (Controller $controller) use ($page) {
              $class = get_class($controller);
              $routeDefintions = $controller->getRoutes();

              foreach ($routeDefintions as $routeDefintion) {
                $routeDefintion->url = trim($routeDefintion->url, '/');
                $url = trim("{$page->url}/{$routeDefintion->url}", '/');
                $action = "$class@{$routeDefintion->action}";

                $route = Route::match($routeDefintion->methods, $url, $action)
                  ->name($page->name);

                $this->app->bind(route_hash($route), function () use ($page) {
                  return $page;
                });
              }
            });
          } catch (Throwable $e) {
            Log::warning("Route {$page->url} could not be registered because {$e->getMessage()}");
          }
        });
      });
  }
}
