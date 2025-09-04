<?php

namespace Netflex\Pages\Components;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

use Netflex\Pages\Contracts\MediaUrlResolvable;
use Netflex\Pages\MediaPreset;

class Image extends Component
{
  protected ?object $settings;

  const MODE_ORIGINAL = 'o';
  const MODE_FILL = 'fill';
  const MODE_EXACT = 'e';
  const MODE_FIT = 'rc';
  const MODE_PORTRAIT = 'p';
  const MODE_LANDSCAPE = 'l';
  const MODE_AUTO = 'a';
  const MODE_CROP = 'c';
  const MODE_FIT_DIRECTION = 'rcf';

  const DIRECTION_TOP = 't';
  const DIRECTION_TOP_LEFT = 'tl';
  const DIRECTION_TOP_RIGHT = 'tr';

  const DIRECTION_BOTTOM = 'b';
  const DIRECTION_BOTTOM_LEFT = 'bl';
  const DIRECTION_BOTTOM_RIGHT = 'br';

  const DIRECTION_LEFT = 'l';
  const DIRECTION_RIGHT = 'r';
  const DIRECTION_CENTER = 'c';

  public function __construct(
    $area = null,
    $size = null,
    $width = null,
    $height = null,
    $mode = Image::MODE_EXACT,
    $color = '0,0,0',
    $direction = null,
    $src = null,
    $alt = null,
    $title = null,
    $class = null,
    $style = null,
    $preset = null,
    $cdn = null
  ) {
    $explicitWidth = $width !== null;
    $explicitHeight = $height !== null;

    $useExplicitWidthAndHeight = Config::get('media.options.image.setWidthAndHeightAttributes', false);

    if (!$useExplicitWidthAndHeight) {
      $explicitHeight = null;
      $explicitWidth = null;
    }

    $mode = $width && !$height ? self::MODE_LANDSCAPE : $mode;
    $mode = $height && !$width ? self::MODE_PORTRAIT : $mode;
    $height = !$height && $width ? $width : $height;
    $width = !$width && $height ? $height : $width;

    if ($preset) {
      if ($preset = MediaPreset::find($preset)) {
        $cdn = $preset->cdn ?? $cdn;
        $mode = $preset->mode ? $preset->mode : $mode;
        $height = $preset->height ? $preset->height : $height;
        $width = $preset->width ? $preset->width : $width;
        $color = $preset->fill ?? $color;
      }
    }

    $this->settings = (object) [
      'cdn' => $cdn ?? null,
      'area' => $area ?? null,
      'size' => $size ?? (($width && $height) ? "{$width}x{$height}" : null),
      'explicitWidth' => $explicitWidth,
      'explicitHeight' => $explicitHeight,
      'mode' => $mode,
      'color' => $color,
      'direction' => $direction,
      'src' => $src,
      'alt' => $alt,
      'title' => $title,
      'class' => $class,
      'style' => $style,
      'inline' => (bool) ($area ?? false),
      'content' => null
    ];

    if ($this->settings->inline) {
      $area = $this->settings->area;
      if (current_mode() === 'edit') {
        $this->settings->area = blockhash_append($area);
        $area = $this->settings->area;
        $this->settings->content = insert_content_if_not_exists($area, 'image');
      } else {
        $this->settings->content = content($area, null);
      }

      if ($this->settings->content) {
        $this->settings->src = $this->settings->content ?? null;
        if ($this->settings->src) {
          $this->settings->alt = $this->settings->src->description ?? null;
          $this->settings->title = $this->settings->src->title ?? null;
        }
      }
    }
  }

  public function id()
  {
    if ($this->settings->inline) {
      return $this->settings->content->id ?? null;
    }
  }

  public function width()
  {
    if ($this->settings->explicitWidth) {
      @list($width) = explode('x', $this->size());
      if ($width ?? null) {
        return (int) $width;
      }
    }

    return null;
  }

  public function height()
  {
    if ($this->settings->explicitHeight) {
      @list($_, $height) = explode('x', $this->size());
      if ($height ?? null) {
        return (int) $height;
      }
    }

    return null;
  }

  public function inline()
  {
    return $this->settings->inline;
  }

  public function size()
  {
    $size = $this->settings->size;

    if (is_string($size)) {
      $size = strtolower($size);

      if (strpos($size, 'x') > 0) {
        list($width, $height) = explode('x', $size);
        return "{$width}x{$height}";
      }

      if (is_numeric($size)) {
        return "{$size}x{$size}";
      }

      return $size;
    }

    if (is_int($size)) {
      return "{$size}x{$size}";
    }

    if (is_array($size)) {
      list($width, $height) = $size;
      return "{$width}x{$height}";
    }

    return (!$size && current_mode() === 'edit' && !$this->settings->content) ? '256x256' : null;
  }

  public function mode()
  {
    if (!$this->size()) {
      return Image::MODE_ORIGINAL;
    }

    $mode = $this->settings->mode ?? null;
    $mode = (!$mode && ($this->settings->size ?? null)) ? static::MODE_EXACT : $mode;
    return $mode ?? Image::MODE_ORIGINAL;
  }

  public function direction()
  {
    $direction = $this->settings->direction ?? null;
    return $direction;
  }

  public function color()
  {
    $color = $this->settings->color ?? null;
    return $color;
  }

  public function path()
  {
    if ($this->settings->src instanceof HtmlString) {
      $this->settings->src = (string) $this->settings->src;
    }

    if ($this->settings->src instanceof MediaUrlResolvable) {
      return $this->settings->src->getPathAttribute();
    }

    if (is_object($this->settings->src) && property_exists($this->settings->src, 'path')) {
      return $this->settings->src->path;
    }

    if (is_object($this->settings->src) && property_exists($this->settings->src, 'image')) {
      return $this->settings->src->image;
    }

    if (is_array($this->settings->src) && array_key_exists('path', $this->settings->src)) {
      return $this->settings->src['path'];
    }

    if (is_array($this->settings->src) && array_key_exists('image', $this->settings->src)) {
      return $this->settings->src['image'];
    }

    if (is_string($this->settings->src)) {
      return $this->settings->src;
    }
  }

  public function src()
  {
    $src = $this->path();
    $src = $src ?
      media_url(
        $src,
        $this->size(),
        $this->mode(),
        $this->color(),
        $this->direction(),
        [],
        $this->settings->cdn
      ) : $src;

    if (!$src && current_mode() === 'edit') {
      return placeholder_image_url($this->size(), $this->settings->cdn);
    }

    return $src;
  }

  public function alt()
  {
    return $this->settings->alt;
  }

  public function title()
  {
    return $this->settings->title;
  }

  public function class()
  {
    return $this->settings->class ?? null;
  }

  public function style()
  {
    return $this->settings->style ?? null;
  }

  public function shouldRender()
  {
    return current_mode() === 'live' && !$this->path() ? false : true;
  }

  /**
   * Get the view / contents that represent the component.
   *
   * @return \Illuminate\View\View|string
   */
  public function render()
  {
    return view('netflex-pages::image');
  }
}
