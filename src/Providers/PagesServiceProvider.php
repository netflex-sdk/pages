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

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
    //
  }

  protected function registerBladeDirectives()
  {
    View::addNamespace('netflex', __DIR__ . '/../views');

    Blade::if('mode', function (...$modes) {
      return if_mode(...$modes);
    });

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

    Blade::directive('inline', function ($expression) {
      return "<?php echo inline($expression); ?>";
    });

    Blade::directive('image', function ($expression) {
      return "<?php echo image($expression); ?>";
    });

    Blade::directive('picture', function ($expression) {
      return "<?php echo picture($expression); ?>";
    });

    Blade::directive('editorTools', function () {
        return "<?php echo editor_tools(); ?>";
    });

    Blade::directive('seo', function () {
        return "<?php echo seo(); ?>";
    });
  }
}
