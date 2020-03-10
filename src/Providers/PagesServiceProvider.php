<?php

namespace Netflex\Pages\Providers;

use Netflex\Pages\Components\EditorButton;
use Netflex\Pages\Components\Image;
use Netflex\Pages\Components\Picture;
use Netflex\Pages\Components\Blocks;
use Netflex\Pages\Components\Inline;
use Netflex\Pages\Components\EditorTools;
use Netflex\Pages\Components\Seo;

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
