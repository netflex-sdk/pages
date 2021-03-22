<?php

namespace Netflex\Pages\Providers;

use Illuminate\Support\Facades\App;
use Netflex\Pages\Components\EditorButton;
use Netflex\Pages\Components\Image;
use Netflex\Pages\Components\Picture;
use Netflex\Pages\Components\Blocks;
use Netflex\Pages\Components\Inline;
use Netflex\Pages\Components\EditorTools;
use Netflex\Pages\Components\Seo;
use Netflex\Pages\Components\StaticContent;
use Netflex\Pages\Components\BackgroundImage;
use Netflex\Pages\Components\Component;
use Netflex\Pages\Components\Nav;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;

use Illuminate\Support\ServiceProvider;
use Netflex\Pages\Components\Breadcrumbs;

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
    $this->publishes([
      __DIR__ . '/../Config/media.php' => $this->app->configPath('media.php')
    ], 'config');

    $this->publishes([
      __DIR__ . '/../Config/pages.php' => $this->app->configPath('pages.php')
    ], 'config');
  }

  protected function registerBladeDirectives()
  {
    View::addNamespace('nf', __DIR__ . '/../views');

    $prefix = Config::get('pages.prefix', '');

    $components = Config::get('pages.components', [
      EditorButton::class,
      Image::class,
      Picture::class,
      Blocks::class,
      Inline::class,
      EditorTools::class,
      Seo::class,
      BackgroundImage::class,
      Nav::class,
      StaticContent::class,
    ]);

    foreach ($components as $alias => $component) {
      Blade::component($component, (is_string($alias) ? $alias : null), $prefix);
    }


    Blade::if('mode', function (...$modes) {
      return if_mode(...$modes);
    });

    Blade::if('domain', function ($domain) {
      return current_domain() === $domain;
    });

    Blade::directive('content', function ($expression) {
      return "<?php echo content($expression); ?>";
    });

    Blade::if('production', function () {
      return in_production();
    });

    Blade::if('development', function () {
      return in_development();
    });

    foreach (['edit', 'preview', 'live'] as $mode) {
      Blade::directive($mode, function ($expression) use ($mode) {
        return "<?php if({$mode}_mode()) { echo " . $expression . "; } ?>";
      });
    }
  }
}
