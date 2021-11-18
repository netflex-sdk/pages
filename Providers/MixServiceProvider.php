<?php

namespace Netflex\Pages\Providers;

use Netflex\Pages\Mix;

use Illuminate\Support\ServiceProvider;

class MixServiceProvider extends ServiceProvider
{
  /**
   * @return void
   */
  public function register()
  {
    $this->app->bind(\Illuminate\Foundation\Mix::class, Mix::class);
  }

  public function boot()
  {
    //
  }
}
