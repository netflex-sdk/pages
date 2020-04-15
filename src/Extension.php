<?php

namespace Netflex\Pages;

use Illuminate\Support\Facades\App;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

use Netflex\Pages\Exceptions\NotImplementedException;

abstract class Extension implements Renderable, Responsable {
  protected $view;
  protected $name;
  protected $data = [];

  public function handle (Request $request) {
      return $this->toResponse($request);
  }

  public function render () {
    if ($this->view) {
      return View::make($this->view, $this->data ?? []);
    }

    if ($this->data && isset($this->data['view'])) {
      return View::make($this->data['view'], $this->data ?? []);
    }

    throw new NotImplementedException(static::class, 'render');
  }

  public function toResponse ($request) {
    return $this->render();
  }

  /**
   * @param string $alias
   * @param string $extension
   * @return void
   */
  public static function register ($alias, $extension) {
    return App::bind('__nf_extension__' . $alias, function () use ($extension) {
      return $extension;
    });
  }

  /**
   * @param string $alias
   * @param array $data
   * @return Extension|null
   */
  public static function resolve ($alias, $data = []) {
    if (App::has('__nf_extension__' . $alias)) {
      $class = App::get('__nf_extension__' . $alias);
      return (new $class($data));
    }
  }
}
