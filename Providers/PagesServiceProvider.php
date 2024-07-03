<?php

namespace Netflex\Pages\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Netflex\Pages\Components\BackgroundImage;
use Netflex\Pages\Components\Blocks;
use Netflex\Pages\Components\EditorButton;
use Netflex\Pages\Components\EditorTools;
use Netflex\Pages\Components\Image;
use Netflex\Pages\Components\Inline;
use Netflex\Pages\Components\Nav;
use Netflex\Pages\Components\Picture;
use Netflex\Pages\Components\Seo;
use Netflex\Pages\Components\StaticContent;
use Netflex\Pages\Controllers\Controller;

class PagesServiceProvider extends ServiceProvider
{
  /**
   * @return void
   */
  public function register()
  {
    $this->registerComponents();
    $this->registerDirectives();
  }

  public function boot()
  {
    $this->publishes([
      __DIR__ . '/../config/media.php' => $this->app->configPath('media.php')
    ], 'config');

    $this->mergeConfigFrom(
      __DIR__ . '/../config/media.php',
      'media'
    );

    $this->publishes([
      __DIR__ . '/../config/pages.php' => $this->app->configPath('pages.php')
    ], 'config');

    $this->mergeConfigFrom(
      __DIR__ . '/../config/pages.php',
      'pages'
    );

    $this->mergeConfigFrom(
      __DIR__ . '/../config/automation_emails/tags.php',
      'automation_emails.tags'
    );
    $this->publishes([
      __DIR__ . '/../config/automation_emails/tags.php' => $this->app->configPath('automation_emails/tags.php')
    ], 'automation-mail-configs');

    $this->loadViewsFrom(__DIR__ . '/../resources/views', 'netflex-pages');

    $this->publishes([
      __DIR__ . '/../resources/views' => base_path('resources/views/vendor/netflex-pages'),
    ], 'views');

    if ($this->app->bound('events')) {
      Controller::setEventDispatcher($this->app['events']);
    }
  }

  protected function registerComponents()
  {
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

    $this->loadViewsFrom(__DIR__ . '/../resources/views', 'pages');

    if ($prefix) {
      $this->loadViewComponentsAs($prefix, $components);
    } else {
      foreach ($components as $alias => $component) {
        Blade::component($component, (is_string($alias) ? $alias : null));
      }
    }
  }

  protected function registerDirectives()
  {
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

    Blade::directive('page', function ($name = null) {
      $name = $name ?: '_page';
      $name = trim($name, "\"'");
      return "<?php echo '<input type=\"hidden\" name=\"" . $name . "\" value=\"' . (current_page() ? current_page()->id : null) . '\">'; ?>";
    });

    Blade::directive('blockhash', function ($name = null) {
      $name = $name ?: '_blockhash';
      $name = trim($name, "\"'");
      return "<?php echo '<input type=\"hidden\" name=\"" . $name . "\" value=\"' . blockhash() . '\">'; ?>";
    });
  }
}
