<?php

namespace Netflex\Pages\Types;

use Netflex\Pages\Types\Picture;

class Image extends Picture
{
  /** @var string */
  protected $type = 'image';

  /** @var string */
  protected $view = 'netflex::image';

  protected function getClass () {
    return $this->settings['class'] ?? null;
  }

  protected function getStyle () {
    return $this->settings['style'] ?? null;
  }

  protected function getViewData () {
    return [
      'id' => $this->content->id ?? null,
      'area' => $this->alias,
      'type' => $this->type,
      'crop' => $this->getCrop(),
      'size' => $this->getSize(),
      'src' => $this->getSrc(),
      'alt' => $this->getAlt(),
      'title' => $this->getTitle(),
      'editable' => $this->settings['editable'] ?? true,
      'class' => $this->getClass(),
      'style' => $this->getStyle()
    ];
  }
}
