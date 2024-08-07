<?php

namespace Netflex\Pages\Providers;

use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravelium\Sitemap\Sitemap;
use Netflex\API\Facades\API;
use Netflex\Foundation\Redirect;
use Netflex\Newsletters\Newsletter;
use Netflex\Notifications\Automation\AutomationEmail;
use Netflex\Pages\AbstractPage;
use Netflex\Pages\Contracts\CompilesException;
use Netflex\Pages\Controllers\Controller;
use Netflex\Pages\Controllers\ControllerNotImplementedController;
use Netflex\Pages\Controllers\PageController;
use Netflex\Pages\Events\CacheCleared;
use Netflex\Pages\Events\CacheStoreClearRequest;
use Netflex\Pages\Exceptions\InvalidControllerException;
use Netflex\Pages\Exceptions\InvalidRouteDefintionException;
use Netflex\Pages\JwtPayload;
use Netflex\Pages\Middleware\BindPage;
use Netflex\Pages\Middleware\GroupAuthentication;
use Netflex\Pages\Middleware\JwtProxy;
use Netflex\Pages\Page;
use Netflex\Pages\PreviewRequest;
use ReflectionClass;
use Throwable;

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
          trim($redirect->source_url),
          trim($redirect->target_url),
          $redirect->type
        )->name($redirect->id);
      });
  }

  public function beforeHandlePage(AbstractPage $page)
  {
    // Not implemented
  }

  protected function resolveControllerClass(AbstractPage $page): string
  {
    $pageController = Config::get('pages.controller') ?? PageController::class;
    $controllerNotImplementedController = ControllerNotImplementedController::class;

    $controllerName = $page->template->controller ?? null;
    $controller = $controllerName ? "\\{$this->namespace}\\{$controllerName}" : $pageController;

    return trim(class_exists($controller) ? $controller : "\\{$controllerNotImplementedController}", '\\');
  }

  protected function handlePage(Request $request, JwtPayload $payload)
  {
    if ($page = Page::model()::findOrFail($payload->page_id)) {
      if ($payload->revision_id ?? false) {
        $page->loadRevision($payload->revision_id);
      }

      return $this->renderPage($page, $request);
    }
  }

  protected function renderPage(AbstractPage $page, Request $request)
  {
    current_page($page);

    $locale = null;

    if ($page->lang) {
      $locale = $page->lang;
    } else {
      $master = $page->master;
      if ($master && $master->lang) {
        $locale = $master->lang;
      }
    }

    if ($locale) {
      App::setLocale($locale);
      Carbon::setLocale($locale);
    }

    $this->beforeHandlePage($page);

    $class = $this->resolveControllerClass($page);

    if (!$class) {
      $page->toResponse($request);
    }

    /** @var PageController $controller */
    $controller = App::make($class);
    $previousPage = current_page();
    current_page($page);

    $route = collect($controller->getRoutes())
      ->first(function ($route) {
        return (in_array($route->url, ['/', '']) || $route->action === 'index') && in_array('GET', $route->methods);
      });

    current_page($previousPage);

    if ($route && method_exists($controller, $route->action)) {
      return $this->callWithInjectedDependencies($controller, $route->action);
    }

    return $controller->fallbackIndex();
  }

  protected function handleEntry(Request $request, JwtPayload $payload)
  {
    if ($payload->structure_id) {
      $structure = Cache::rememberForever('structure/' . $payload->structure_id, function () use ($payload) {
        return API::get('builder/structures/' . $payload->structure_id . '/basic');
      });
    }

    if (!$structure) {
      abort(404, 'Structure not found or not set in payload.');
    }

    if (!isset($structure->config->previewController) && !isset($payload->controller)) {
      abort(400, 'previewController setting missing or misformed in structure config.');
    }

    if (isset($payload->controller) && $payload->controller) {
      list($controller, $action) = explode('@', $payload->controller);
    } elseif (isset($structure->config->previewController)) {
      list($controller, $action) = explode('@', $structure->config->previewController->value);
    }

    $controller = trim("\\{$this->namespace}\\{$controller}", '\\');

    if (!$controller || !$action) {
      abort(400, 'previewController setting missing or misformed in structure config.');
    }

    $previewRequest = new PreviewRequest($payload);

    return app($controller)->{$action}($previewRequest);
  }

  protected function handleExtension(Request $request, $payload)
  {
    /** @var JwtPayload $payload */
    if ($alias = $payload->view) {
      if ($extension = resolve_extension($alias, json_decode(json_encode($payload->getAttributes()), true))) {
        return $extension->handle($request);
      }
    }

    return abort(404);
  }

  protected function handleNewsletter(Request $request, JwtPayload $payload)
  {
    if ($newsletter = Newsletter::where('id', $payload->newsletter_id)->first()) {

      $automationMail = null;
      $newsletter->automation != "1" || $automationMail = AutomationEmail::find($newsletter->id);

      if ($page = $newsletter->page) {

        $page = $page->loadRevision($page->revision);
        current_newsletter($newsletter);

        if ($automationMail) {
          $this->extendEditorToolsToIncludeTags($automationMail);
        }

        if ($payload->mode === 'preview') {
          return $newsletter->renderPreview($payload->preview_type ?? 'html');
        }

        if ($payload->mode === 'live') {
          return $newsletter->renderAndSave();
        }

        return $this->renderPage($page, $request);
      }
    }

    /** Ugly hack to work around newsletter indexing issues */
    API::put('elasticsearch/newsletter/' . $payload->newsletter_id);
    $newsletter = new Newsletter;
    $newsletter->id = $payload->newsletter_id;
    $reflection = new ReflectionClass($newsletter);
    $method = $reflection->getMethod('getCacheIdentifier');
    $method->setAccessible(true);
    $cacheKey = $method->invoke($newsletter, $payload->newsletter_id);
    Cache::forget($cacheKey);
    /** End of ugly hack */

    return 'Newsletter not indexed, please try again in a few minutes.';
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
      $keys = $request->get('keys', []);

      if (is_string($keys)) {
        $keys = array_values(array_filter(explode(',', $keys)));
      }

      if ($key = $request->get('key')) {
        $keys[] = $key;
      }

      $keys = array_unique($keys);

      foreach ($keys as $key) {
        if ($key) {
          CacheStoreClearRequest::dispatch($key, $request);
        }
      }

      $success = false;

      if (!count($keys)) {
        return ['success' => false, 'message' => 'Key is missing'];
      }

      foreach ($keys as $key) {
        $key = trim($key);

        if ($key = $request->get('key')) {
          if ($key === 'pages') {
            clear_route_cache();
          }

          if ($key === 'redirects') {
            clear_route_cache();
          }

          if (Cache::has($key)) {
            Cache::forget($key);
            CacheCleared::dispatch($key);
            $success = true;
          }
        }

        if ($success) {
          return ['success' => true, 'message' => 'Key(s) deleted'];
        } else {
          return ['success' => false, 'message' => 'Some Key(s) does not exist'];
        }
      }
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
              case 'newsletter':
                return $this->handleNewsletter($request, $payload);
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

    $routeCache = Cache::get(static::ROUTE_CACHE);

    if (!$routeCache) {
      $compiledRoutes = [];
      $compiledSubRoutes = [];

      $pages = Page::model()::all()->filter(function ($page) {
        return $page->type === 'page' && $page->template && $page->published;
      });

      foreach ($pages as $page) {
        /** @var Page $page */
        $class = $this->resolveControllerClass($page);

        /** @var Controller|null $controllerInstance */
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

          $previousPage = current_page();
          current_page($page);
          $routeDefintions = $controllerInstance->getRoutes();
          current_page($previousPage);

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

            $where = [];
            $compiledWhere = '';

            if (isset($routeDefintion->where)) {
              $where = json_decode(json_encode($routeDefintion->where), true);
            }

            if (count($where)) {
              $compiledWhere = '->where([';
              foreach ($where as $key => $value) {
                $compiledWhere .= '"' . $key . '" => "' . $value . '",';
              }
              $compiledWhere = rtrim($compiledWhere, ',');
              $compiledWhere .= '])';
            }

            $compiledRoute = '\\Illuminate\Support\Facades\App::bind(route_hash(' . '\\Illuminate\\Support\\Facades\\' . ($domain ? ('Route::domain("' . $domain . '")->match(') : ('Route::match(')) . json_encode($routeDefintion->methods) . ',"' . $url . '","' . $action . '")' . $compiledWhere . '->name("' . ($name ?? $page->id) . '")' . '),function(){return \\' . Page::class . '::model()::find(' . $page->id . ');});';

            if ($routeDefintion->index) {
              $compiledRoutes[] = $compiledRoute;
            } else {
              $compiledSubRoutes[] = $compiledRoute;
            }
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

          $compiledRoute = '\\Illuminate\\Support\\Facades\\' . ($page->domain ? 'Route::domain("")->any(' : 'Route::any(') . '"' . rtrim($page->url, '/') . '/{any?}",function() { clear_route_cache(); throw new ' . $exception . ';})->name("' . $page->id . '");';

          if (!isset($routeDefintion) || $routeDefintion->index) {
            $compiledRoutes[] = $compiledRoute;
          } else {
            $compiledSubRoutes[] = $compiledRoute;
          }
        }
      }

      $routeSource = implode("\n", [
        ...$compiledRoutes,
        ...$compiledSubRoutes,
      ]);

      $routeCache = $routeSource;
      Cache::rememberForever(static::ROUTE_CACHE, fn() => $routeCache);
    }

    Route::middleware('netflex')
      ->namespace($this->namespace)
      ->group(function () use ($routeCache) {
        if ($routeCache) {
          try {
            return eval($routeCache);
          } catch (Exception $e) {
            clear_route_cache();
            throw $e;
          }
        }
      });

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

  /**
   * @param $automationMail
   * @return void
   */
  public function extendEditorToolsToIncludeTags($automationMail): void
  {
    $this->app->extend('__editor_tools__', function ($string) use ($automationMail) {

      $tags = [];

      foreach (config("automation_emails.tags.mails.$automationMail->id.include", []) as $section) {
        foreach (Arr::dot(config("automation_emails.tags.includes.$section", [])) as $key => $value) {
          data_set($tags, $key, $value);
        }
      }

      foreach (Arr::dot(config("automation_emails.tags.mails.$automationMail->id.attributes", [])) as $key => $value) {
        data_set($tags, $key, $value);
      }

      return $this->insertBefore($string ?? '', '<div id="netflex-advanced-content-widget-header">', [
        '<!-- PRE: Addons -->',
        (string)view('pages::newsletter-tags', compact('tags')),
        '<!-- POST: Addons -->'
      ]);
    });
  }

  private function insertBefore(string $whole, string $before, $to_insert): string
  {
    $to_insert = is_array($to_insert) ? implode("", $to_insert) : (string)$to_insert;
    $parts = explode($before, $whole);

    return ($parts[0] ?? '') . $to_insert . $before . ($parts[1] ?? '');
  }

  private function insertAfter(string $whole, string $after, string $to_insert): string
  {
    $parts = explode($after, $whole);
    return ($parts[0] ?? '') . $after . $to_insert . ($parts[1] ?? '');
  }
}
