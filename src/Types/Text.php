<?php

namespace Netflex\Pages\Types;

use Netflex\Pages\Types\Content;

class Text extends Content
{
  /** @var string */
  protected $type = 'html';

  /** @var string */
  protected $view = 'netflex::text';

  protected function getViewData () {
    return [
      'tag' => $this->settings['tag'],
      'id' => $this->content->id ?? null,
      'area' => $this->alias,
      'type' => $this->type,
      'class' => $this->settings['class'] ?? null,
      'style' => $this->settings['style'] ?? null,
      'editable' => $this->settings['editable'] ?? true,
      'html' => $this->content->{$this->type} ?? $this->settings['placeholder'] ?? null
    ];
  }
}
