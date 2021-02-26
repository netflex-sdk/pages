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
use Illuminate\Support\Facades\View;

use Illuminate\Support\ServiceProvider;

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

    Blade::component(EditorButton::class);
    Blade::component(Image::class);
    Blade::component(Image::class, 'img');
    Blade::component(Picture::class);
    Blade::component(Blocks::class);
    Blade::component(Inline::class);
    Blade::component(EditorTools::class);
    Blade::component(Seo::class);
    Blade::component(BackgroundImage::class);
    Blade::component(Nav::class);
    Blade::component(StaticContent::class);
    Blade::component(Component::class);

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
