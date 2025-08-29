<?php

namespace Netflex\Pages\Components;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use Netflex\Pages\Contracts\MediaUrlResolvable;
use Netflex\Pages\MediaPreset;
use Netflex\Pages\Exceptions\InvalidPresetException;

class Picture extends Component
{
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

  protected $src;
  protected $area;
  protected $content;
  protected $title;
  protected $alt;
  protected $preset = 'default';

  public $pictureClass = null;
  public $imageClass = null;
  public $mode;
  public $size;
  public $fill;
  public $inline;
  public $direction;
  public $loading;
  public $useExplicitWidthAndHeight;

  /**
   * Create a new component instance.
   *
   * @return void
   */
  public function __construct($area = null, $alt = null, $title = null, $src = null, $mode = null, $width = null, $height = null, $size = null, $fill = null, $imageClass = null, $pictureClass = null, $preset = 'default', $direction = null, $loading = 'lazy')
  {
    $this->inline = !!$area;
    $this->area = $area;
    $this->src = $src;
    $this->preset = $preset;
    $this->imageClass = $imageClass;
    $this->pictureClass = $pictureClass;
    $this->mode = $mode;
    $this->size = $size;
    $this->fill = $fill;
    $this->title = $title;
    $this->alt = $alt;
    $this->direction = $direction;
    $this->loading = $loading;

    $width = $width ?? $height ?? null;
    $height = $height ?? $width ?? null;

    $this->useExplicitWidthAndHeight = Config::get('media.options.image.setWidthAndHeightAttributes', false);

    $this->size = $this->size ? $this->size : null;
    $this->size = !$this->size && $width && $height ? ((int) $width . 'x' . (int) $height) : $this->size;

    if ($this->inline) {
      insert_content_if_not_exists(blockhash_append($this->area), 'image');
      $this->content = content($this->area, null);
    }
  }

  public function editorSettings()
  {
    if ($this->inline && current_mode() === 'edit') {
      $preset = $this->preset();

      return [
        'id' => 'e-' . ($this->content->id ?? null) . '-picture-' . uniqid(),
        'data-content-type' => 'image',
        'data-content-field' => 'image',
        'data-content-dimensions' => $preset->size,
        'data-content-compressiontype' => $preset->mode,
        'data-content-id' => ($this->content->id ?? null)
      ];
    }

    return [];
  }

  public function src()
  {
    if ($this->inline) {
      return content($this->area, 'image')->path ?? null;
    }

    if ($this->src instanceof HtmlString) {
      $this->src = (string) $this->src;
    }

    if ($this->src instanceof MediaUrlResolvable) {
      return $this->src->getPathAttribute();
    }

    if (is_object($this->src) && property_exists($this->src, 'path')) {
      return $this->src->path;
    }

    if (is_array($this->src) && array_key_exists('path', $this->src)) {
      return $this->src['path'];
    }

    return $this->src;
  }

  /**
   * @return MediaPreset
   * @throws InvalidPresetException
   */
  public function preset()
  {
    $default = Config::get("media.presets.default", [
      'mode' => static::MODE_FIT,
      'resolutions' => ['1x', '2x', '3x'],
      'direction' => static::DIRECTION_CENTER
    ]);

    if ($preset = Config::get("media.presets.{$this->preset}")) {
      $preset['size'] = $this->size ?? $preset['size'] ?? $default['size'] ?? null;
      $preset['mode'] = $this->mode ?? $preset['mode'] ?? $default['mode'] ?? static::MODE_ORIGINAL;
      $preset['fill'] = $this->fill ?? $preset['fill'] ?? $default['fill'] ?? null;
      $preset['direction'] = $this->direction ?? $preset['direction'] ?? $default['direction'] ?? null;
      $preset['resolutions'] = $preset['resolutions'] ?? $default['resolutions'] ?? null;
      $preset['breakpoints'] = $preset['breakpoints'] ?? $default['breakpoints'] ?? null;

      return new MediaPreset($preset);
    }

    throw new InvalidPresetException($this->preset);
  }

  public function defaultSrc()
  {
    $preset = $this->preset();

    if ($src = $this->src()) {
      return media_url($src, $preset);
    }

    if ($this->inline && current_mode() === 'edit') {
      $size = $preset->size === '0x0' ? '256x256' : $preset->size;
      return 'https://via.placeholder.com/' . $size;
    }
  }

  public function defaultPaths()
  {

    $resolutionPaths = [];
    $preset = $this->preset();

    foreach ($preset->resolutions as $resolution) {
      $resolutionPaths[$resolution] = media_url($this->src(), $preset, null, null, null, ['res' => $resolution]);
    }

    return $resolutionPaths;
  }

  public function defaultSrcSet()
  {
    $paths = $this->defaultPaths();
    $srcset = [];

    foreach ($paths as $resolution => $path) {
      $srcset[] = $path . ' ' . $resolution;
    }

    return implode(', ', $srcset);
  }

  public function srcSets()
  {
    $srcSets = [];

    foreach ($this->preset()->breakpoints as $breakpoint => $preset) {
      /** @var MediaPreset */
      $preset = $preset;

      $srcSet = [
        'breakpoint' => $breakpoint,
        'maxWidth' => $preset->maxWidth,
        'mqMaxWidth' => $preset->maxWidth - Config::get('media.options.breakpoints.media_query_max_width_subtract', 0),
        'paths' => []
      ];

      if ($this->src()) {
        foreach ($preset->resolutions as $resolution) {
          $srcSet['paths'][$resolution] = media_url($this->src(), $preset, null, null, null, [
            'src' => "{$preset->maxWidth}w",
            'res' => $resolution,
          ]);
        }

        $mergedSets = [];

        foreach ($srcSet['paths'] as $resolution => $path) {
          $mergedSets[] = $path . ' ' . $resolution;
        }

        $srcSet['sources'] = $srcSet['paths'];
        $srcSet['paths'] = new HtmlString(implode(' ,', $mergedSets));
      } else {
        $srcSet['sources'] = ['1x' => 'https://via.placeholder.com/' . $preset->size];
        $srcSet['paths'] = 'https://via.placeholder.com/' . $preset->size;
      }

      $srcSets[] = $srcSet;
    }

    return $srcSets;
  }

  public function shouldRender()
  {
    return current_mode() === 'edit' || $this->src();
  }

  public function title()
  {
    if ($this->content) {
      return $this->content->title ?? $this->title;
    }

    return $this->title;
  }

  public function alt()
  {
    if ($this->content) {
      return $this->content->description ?? $this->alt;
    }
    return $this->alt;
  }

  /**
   * Get the view / contents that represent the component.
   *
   * @return \Illuminate\View\View|string
   */
  public function render()
  {
    return view('netflex-pages::picture');
  }
}
