<?php

namespace Netflex\Pages\Controllers;

use Netflex\Support\Concerns\HasEvents;

abstract class Controller extends BaseController
{
  use HasEvents;

  public function __construct()
  {
    $this->bootIfNotBooted();
    $this->initializeTraits();
  }

  /**
   * Register an updated model event with the dispatcher.
   *
   * @param  \Closure|string  $callback
   * @return void
   */
  public static function booting($callback)
  {
    static::registerEvent('booting', $callback);
  }

  /**
   * Register an updated model event with the dispatcher.
   *
   * @param  \Closure|string  $callback
   * @return void
   */
  public static function booted($callback)
  {
    static::registerEvent('booted', $callback);
  }
}
