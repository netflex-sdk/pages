<?php

namespace Netflex\Pages\Types;

use Netflex\Pages\Types\Content;

class Picture extends Content
{
  /** @var string */
  protected $type = 'image';

  /** @var string */
  protected $view = 'netflex::picture';

  protected function getSize () {
    $size = $this->settings['size'] ?? null;
    $size = is_integer($size) ? [$size, $size] : $size;
    $size = is_array($size) ? "$size[0]x$size[1]" : $size;
    $size = !$size && current_mode() === 'edit' ? '128x128' : $size;
    return $size;
  }

  protected function getCrop () {
    $crop = $this->settings['crop'] ?? null;
    $crop = (!$crop && ($this->settings['size'] ?? null)) ? 'rc' : $crop;
    return $crop ?? 'o';
  }

  protected function getFill () {
    $fill = $this->settings['fill'] ?? null;
    return $fill;
  }

  protected function getSrc () {
    $src = $this->content->{$this->type} ?? null;
    $src = $src ?
      media_url(
        $src,
        $this->getSize(),
        $this->getCrop(),
        $this->getFill()
      ) : $src;

    if (!$src && current_mode() === 'edit') {
      return "https://placehold.it/{$this->getSize()}";
    }

    return $src;
  }

  protected function getSrcSets () {
    return picture_srcsets(
      array_merge(['path' => $this->getSrc()], $this->settings)
    );
  }

  protected function getAlt() {
    return $this->settings['alt'] ?? $this->content->description ?? null;
  }

  protected function getTitle () {
    return $this->settings['title'] ?? $this->content->title ?? null;
  }

  protected function getImageClass () {
    return ($this->settings['image'] ?? [])['class'] ?? null;
  }

  protected function getPictureClass () {
    return ($this->settings['picture'] ?? [])['class'] ?? null;
  }

  protected function getPictureStyle () {
    return ($this->settings['picture'] ?? [])['style'] ?? null;
  }

  protected function getImageStyle () {
    return ($this->settings['image'] ?? [])['style'] ?? null;
  }

  protected function getViewData () {
    return [
      'id' => $this->content->id ?? null,
      'area' => $this->alias,
      'type' => $this->type,
      'crop' => $this->getCrop(),
      'size' => $this->getSize(),
      'src' => $this->getSrc(),
      'srcsets' => $this->getSrcSets(),
      'alt' => $this->getAlt(),
      'title' => $this->getTitle(),
      'editable' => $this->settings['editable'] ?? true,
      'image_class' => $this->getImageClass(),
      'image_style' => $this->getPictureStyle(),
      'picture_class' => $this->getPictureClass(),
      'picture_style' => $this->getPictureClass(),
    ];
  }
}
