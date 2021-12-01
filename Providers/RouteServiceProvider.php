<?php

namespace Netflex\Pages\Providers;

use Throwable;
use ReflectionClass;

use API;
use Carbon\Carbon;
use Exception;
use Netflex\Pages\Page;
use Netflex\Pages\Middleware\BindPage;
use Netflex\Pages\Middleware\GroupAuthentication;
use Netflex\Pages\Controllers\PageController;
use Netflex\Pages\Controllers\Controller;
use Netflex\Pages\Middleware\JwtProxy;
use Netflex\Foundation\Redirect;
use Netflex\Pages\JwtPayload;
use Netflex\Pages\PreviewRequest;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravelium\Sitemap\Sitemap;
use Netflex\Pages\Contracts\CompilesException;
use Netflex\Pages\Exceptions\InvalidControllerException;
use Netflex\Pages\Exceptions\InvalidRouteDefintionException;

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

  public const ROUTE_CACHE = 'sdk/cache/routes';

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

    Request::macro('page', function () {
      return current_page();
    });
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
    $this->mapRobots();
    $this->mapSitemap();
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
        )->name($redirect->id);
      });
  }

  protected function handlePage(Request $request, JwtPayload $payload)
  {
    if ($page = Page::model()::findOrFail($payload->page_id)) {
      if ($payload->revision_id ?? false) {
        $page->loadRevision($payload->revision_id);
      }

      current_page($page);

      $controller = $page->template->controller ?? null;
      $pageController = Config::get('pages.controller', PageController::class) ?? PageController::class;
      $class = trim($controller ? ("\\{$this->namespace}\\{$controller}") : "\\{$pageController}", '\\');

      if (!$class) {
        $page->toResponse($request);
      }

      /** @var PageController $controller  */
      $controller = App::make($class);

      $route = collect($controller->getRoutes())
        ->first(function ($route) {
          return in_array($route->url, ['/', '']) || $route->action === 'index';
        });

      if ($route && method_exists($controller, $route->action)) {
        return $this->callWithInjectedDependencies($controller, $route->action);
      }

      return $controller->fallbackIndex();
    }
  }

  protected function handleEntry(Request $request, JwtPayload $payload)
  {
    if ($payload->structure_id) {
      $structure = Cache::rememberForever('structure/' . $payload->structure_id, function () use ($payload) {
        return API::get('builder/structures/' . $payload->structure_id);
      });
    }

    if (!$structure) {
      abort(404, 'Structure not found or not set in payload.');
    }

    if (!isset($structure->config->previewController)) {
      abort(400, 'previewController setting missing or misformed in structure config.');
    }

    list($controller, $action) = explode('@', $structure->config->previewController->value);

    $controller = trim("\\{$this->namespace}\\{$controller}", '\\');

    if (!$controller || !$action) {
      abort(400, 'previewController setting missing or misformed in structure config.');
    }

    $previewRequest = new PreviewRequest($payload);

    return app($controller)->{$action}($previewRequest);
  }

  protected function handleExtension(Request $request, $payload)
  {
    if ($alias = $payload->view) {
      if ($extension = resolve_extension($alias, json_decode(json_encode($payload), true))) {
        return $extension->handle($request);
      }
    }

    return abort(404);
  }

  protected function callWithInjectedDependencies($controller, $method = 'index', $arguments = [])
  {
    return $controller->$method(...$this->injectDependencies($controller, $method, $arguments));
  }

  /**
   * @param \Illuminate\Routing\Controller $class
   * @param string $method
   * @param array $arguments
   * @return array
   */
  protected function injectDependencies(\Illuminate\Routing\Controller $class, $method = 'index', $arguments = [])
  {
    $reflector = new ReflectionClass($class);
    if ($reflector->hasMethod($method)) {
      $params = $reflector->getMethod($method)->getParameters();
      if ($param = array_shift($params)) {
        if ($param && $param->hasType() && !$param->isDefaultValueAvailable() && !$param->isOptional() && $param->getType()->getName() === \Illuminate\Http\Request::class) {
          array_unshift($arguments, app('request'));
        }

        return $arguments;
      }
    }

    return [];
  }

  protected function mapNetflexWellKnownRoutes()
  {
    Route::get('.well-known/netflex/CacheStore', function (Request $request) {
      if ($key = $request->get('key')) {
        if ($key === 'pages') {
          clear_route_cache();
        }

        if (Cache::has($key)) {
          Cache::forget($key);
          return ['success' => true, 'message' => 'Key deleted'];
        }

        return ['success' => false, 'message' => 'Key does not exist'];
      }

      return ['success' => false, 'message' => 'Key is missing'];
    });

    Route::middleware(['web', 'jwt_proxy'])
      ->group(function () {
        Route::any('.well-known/netflex', function (Request $request) {
          if ($payload = jwt_payload()) {
            current_mode($payload->mode);
            editor_tools($payload->edit_tools);
            URL::forceRootUrl($payload->domain);

            switch ($payload->relation) {
              case 'page':
                return $this->handlePage($request, $payload);
              case 'entry':
                return $this->handleEntry($request, $payload);
              case 'extension':
                return $this->handleExtension($request, $payload);
              default:
                break;
            }
          }

          abort(400);
        })->name('Netflex Editor Proxy');
      });
  }

  protected function mapNetflexRoutes()
  {
    $this->mapNetflexWellKnownRoutes();

    $deleteCompiledRoutes = false;

    if (!file_exists(storage_path(static::ROUTE_CACHE . '.php'))) {
      $compiledRoutes = [];

      $pages = Page::model()::all()->filter(function ($page) {
        return $page->type === 'page' && $page->template && $page->published;
      });

      foreach ($pages as $page) {
        /** @var Page */
        $page = $page;

        $controller = $page->template->controller ?? null;
        $pageController = Config::get('pages.controller', PageController::class) ?? PageController::class;
        $class = trim($controller ? ("\\{$this->namespace}\\{$controller}") : "\\{$pageController}", '\\');

        /** @var Controller|null */
        $controllerInstance = null;

        try {
          // Precompute domain bindings for page
          $domain = Cache::rememberForever($page->id . ':domain', function () use ($page) {
            return $page->domain ?? '';
          });

          // We attempt to instantiate the target class
          $controllerInstance = app($class);
          $class = get_class($controllerInstance);

          if (!($controllerInstance instanceof Controller)) {
            throw new InvalidControllerException($class);
          }

          $routeDefintions = $controllerInstance->getRoutes();

          foreach ($routeDefintions as $i => $routeDefintion) {
            if (!isset($routeDefintion->url) || empty($routeDefintion->url)) {
              throw new InvalidRouteDefintionException($class, $routeDefintion, InvalidRouteDefintionException::E_URL);
            }

            if (!isset($routeDefintion->action) || empty($routeDefintion->action)) {
              throw new InvalidRouteDefintionException($class, $routeDefintion, InvalidRouteDefintionException::E_ACTION);
            }

            $methods = collect([$routeDefintion->methods ?? [], $routeDefintion->method ?? null])
              ->flatten()
              ->filter()
              ->filter(function ($method) {
                return in_array($method, ['GET', 'PUT', 'POST', 'PATCH', 'DELETE', 'OPTIONS']);
              })
              ->toArray();

            if (empty($methods)) {
              throw new InvalidRouteDefintionException($class, $routeDefintion, InvalidRouteDefintionException::E_METHODS);
            }

            $routeDefintion->url = trim($routeDefintion->url, '/');
            $url = trim("{$page->url}/{$routeDefintion->url}", '/');
            $action = '\\\\' . str_replace('\\', '\\\\', $class) . "@{$routeDefintion->action}";

            $routeName = null;
            $pageRouteName = $page->config->route_name ?? Str::slug($page->name);

            if (isset($routeDefintion->name)) {
              $routeName = Str::slug($routeDefintion->name);
              $routeName = !$routeName ? ($routeDefintion->url ? Str::slug($routeDefintion->url) : 'index') : $routeName;
            }

            $names = collect([$pageRouteName, $routeName])->filter();
            $name = ($names->count() > 1) ? $names->join('.') : $pageRouteName ?? null;

            $compiledRoutes[] = '\\Illuminate\Support\Facades\App::bind(route_hash(' . '\\Illuminate\\Support\\Facades\\' . ($domain ? ('Route::domain("' . $domain . '")->match(') : ('Route::match(')) . json_encode($routeDefintion->methods) . ',"' . $url . '","' . $action . '")->name("' . ($name ?? $page->id) . '")' . '),function(){return \\' . Page::class . '::model()::find(' . $page->id . ');});';
          }
        } catch (Throwable $e) {
          $deleteCompiledRoutes = true;
          $message = $e->getMessage();
          $code = $e->getCode();

          // The target controller class doesn't exist,
          // we register a wildcard route for the page, so we can throw an error
          // when attempting to route to the page
          $exception = 'Exception("' . str_replace('"', "'", $message) . ($code ? (',' . $code) : null) . '")';

          if ($e instanceof CompilesException) {
            $exception = $e->compile();
          }

          $compiledRoutes[] = '\\Illuminate\\Support\\Facades\\' . ($page->domain ? 'Route::domain("")->any(' : 'Route::any(') . '"' . rtrim($page->url, '/') . '/{any?}",function() { clear_route_cache(); throw new ' . $exception . ';})->name("' . $page->id . '");';
        }
      }

      $routeSource = implode("\n", [
        '<?php',
        '',
        '// Compiled routes generated by Netflex, do not edit manually unless you know what you are doing',
        '',
        ...$compiledRoutes,
        ''
      ]);

      file_put_contents(storage_path(static::ROUTE_CACHE . '.php'), $routeSource);
    }

    Route::middleware('netflex')
      ->namespace($this->namespace)
      ->group(storage_path(static::ROUTE_CACHE . '.php'));

    if ($deleteCompiledRoutes) {
      clear_route_cache();
    }
  }

  protected function mapRobots()
  {
    Route::get('robots.txt', function () {
      $production = app()->env === 'master';

      return response(view('netflex-pages::robots', ['production' => $production]), 200, ['Content-Type' => 'text/plain']);
    })->name('robots.txt');
  }

  protected function mapSitemap()
  {
    Route::get('/sitemap.xml', function () {
      /** @var Sitemap */
      $sitemap = App::make('sitemap');

      $now = Carbon::now()->toDateTimeString();

      $sitemap->setCache('netflex.sitemap', 60);

      if (!$sitemap->isCached()) {

        foreach ($this->getSitemapPages() as $page) {
          /** @var Page */
          $page = $page;
          $sitemap->add(url($page->url), $now, '1.0', 'daily');
        }

        foreach ($this->getSitemapEntries() as $entry) {
          $sitemap->add(url($entry->url), $entry->updated->toDateTimeString(), '1.0', 'daily');
        }
      }

      return $sitemap->render('xml', '/sitemap.xsl');
    })->name('sitemap.xml');
  }

  protected function getSitemapPages()
  {
    return Page::model()::all()
      ->filter(function (Page $page) {
        return $page->type === Page::TYPE_PAGE && $page->published && $page->public && $page->visible;
      });
  }

  protected function getSitemapEntries()
  {
    return [];
  }
}
