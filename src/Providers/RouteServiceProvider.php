<?php

namespace Netflex\Pages\Providers;

use Netflex\Pages\Page;
use Netflex\Pages\Middleware\BindPage;
use Netflex\Pages\Middleware\GroupAuthentication;
use Netflex\Pages\Controllers\PageController;
use Netflex\Pages\Controllers\Controller;

use Netflex\Foundation\Redirect;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Collection;
use Netflex\Pages\Middleware\JwtProxy;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
      $this->registerMiddlewareGroups();
      parent::boot();
    }

    protected function registerMiddlewareGroups()
    {
      $router = $this->app->make('router');

      $router->aliasMiddleware('jwt_proxy', JwtProxy::class);
      $router->aliasMiddleware('bind_page', BindPage::class);
      $router->aliasMiddleware('group_auth', GroupAuthentication::class);

      $router->middlewareGroup('netflex', [
        'web',
        'bind_page',
        'group_auth'
      ]);
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapNetflexRoutes();
        $this->mapRedirects();
        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
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
      Route::middleware('jwt_proxy')
        ->group(function () {

          Route::any('.well-known/netflex', function (Request $request) {
            $payload = $request->get('payload');
            current_mode($payload->mode);
            current_page($payload->page_id);
            editor_tools($payload->edit_tools);

            if ($payload->relation === 'page') {
              return Page::findOrFail($payload->page_id)
                ->loadRevision($payload->revision_id)
                ->toResponse($request);
            }
          });
        });

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
