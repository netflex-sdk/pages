<?php

namespace Netflex\Pages\Types;

use Netflex\Support\ReactiveObject;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\View;

use function GuzzleHttp\debug_resource;

abstract class Content extends ReactiveObject implements Renderable
{
  /** @var string */
  protected $alias;

  /** @var array */
  protected $settings;

  /** @var string */
  protected $type;

  /** @var string */
  protected $view;

  /** @var object|null */
  protected $content;

  public function __construct ($alias, $settings = []) {
    $this->alias = blockhash_append($alias);
    $this->settings = $settings;
  }

  protected function getContent () {
    if ($content = content($this->alias, null)) {
      return $content;
    }

    if (current_mode() === 'edit') {
      return insert_content_if_not_exists($this->alias, $this->type);
    }
  }

  protected function getViewData () {
    return [];
  }

  public function render()
  {
    $this->content = $this->getContent();

    return View::make(
      $this->view,
      $this->getViewData()
    )->render();
  }

  public function __toString()
  {
    return $this->render();
  }
}
