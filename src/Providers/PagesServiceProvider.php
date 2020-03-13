<?php

namespace Netflex\Pages\Providers;

use Netflex\Pages\Components\EditorButton;
use Netflex\Pages\Components\Image;
use Netflex\Pages\Components\Picture;
use Netflex\Pages\Components\Blocks;
use Netflex\Pages\Components\Inline;
use Netflex\Pages\Components\EditorTools;
use Netflex\Pages\Components\Seo;
use Netflex\Pages\Components\GlobalValue;
use Netflex\Pages\Components\Content;
use Netflex\Pages\Components\BackgroundImage;

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
    //
  }

  protected function registerBladeDirectives()
  {
    View::addNamespace('nf', __DIR__ . '/../views');

    Blade::component(EditorButton::class);
    Blade::component(Image::class);
    Blade::component(Picture::class);
    Blade::component(Blocks::class);
    Blade::component(Inline::class);
    Blade::component(EditorTools::class);
    Blade::component(Seo::class);
    Blade::component(Content::class);
    Blade::component(BackgroundImage::class);
    Blade::component(GlobalValue::class, 'static-content');

    Blade::if('mode', function (...$modes) {
      return if_mode(...$modes);
    });

    Blade::directive('content', function ($expression) {
      return "<?php echo content($expression); ?>";
    });
  }
}
