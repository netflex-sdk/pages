<?php

namespace Netflex\Pages\Providers;

use Netflex\Pages\Page;
use Netflex\Pages\Controllers\Controller;
use Netflex\Pages\Controllers\PageController;

use Netflex\Pages\Middleware\BindPage;
use Netflex\Pages\Middleware\GroupAuthentication;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

class PagesServiceProvider extends ServiceProvider
{
  /**
   * @return void
   */
  public function register()
  {
    $this->registerBladeDirectives();
  }

  public function boot()
  {
    $this->registerMiddlewareGroups();
    $this->registerPageRoutes();
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
      'bind_page',
      'group_auth'
    ]);
  }

  protected function registerPageRoutes()
  {
    Route::group(['middleware' => 'netflex'], function () {
      $pages = Page::all()->filter(function ($page) {
        return $page->type === 'page' && $page->template;
      });

      foreach ($pages as $page) {
        tap(new PageController, function (Controller $controller) use ($page) {
          $class = get_class($controller);
          $routeDefintions = $controller->getRoutes();

          foreach ($routeDefintions as $routeDefintion) {
            $routeDefintion->url = trim($routeDefintion->url, '/');
            $url = trim("{$page->url}/{$routeDefintion->url}", '/');
            $action = "$class@{$routeDefintion->action}";

            $route = Route::match($routeDefintion->methods, $url, $action)
              ->name($page->id);

            $this->app->bind(spl_object_hash($route), $page);
          }
        });
      }
    });
  }
}
