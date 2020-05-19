<?php

namespace Netflex\Pages\Components;

use Illuminate\Support\Facades\Config;
use Illuminate\View\Component;
use Netflex\Pages\Exceptions\InvalidPresetException;
use Netflex\Pages\MediaPreset;

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

  protected $src;
  protected $area;
  protected $inline;
  protected $content;
  protected $title;
  protected $alt;
  protected $preset = 'default';

  public $pictureClass = null;
  public $imageClass = null;
  public $mode;
  public $size;
  public $fill;

  /**
   * Create a new component instance.
   *
   * @return void
   */
  public function __construct($area = null, $alt = null, $title = null, $src = null, $mode = null, $width = null, $height = null, $size = null, $fill = null, $imageClass = null, $pictureClass = null, $preset = 'default')
  {
    $this->inline = !!$area;
    $this->area = blockhash_append($area);
    $this->src = $src;
    $this->preset = $preset;
    $this->imageClass = $imageClass;
    $this->pictureClass = $pictureClass;
    $this->mode = $mode;
    $this->size = $size;
    $this->fill = $fill;
    $this->title = $title;
    $this->alt = $alt;
    $this->size = $this->size ?? ((int) $width . 'x' . (int) $height);

    if ($this->inline) {
      insert_content_if_not_exists($this->area, 'image');
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

    return $this->src;
  }

  /**
   * @return MediaPreset
   */
  protected function preset()
  {
    if ($preset = Config::get("media.presets.{$this->preset}")) {
      $preset['size'] = $this->size ?? $preset['size'] ?? null;
      $preset['mode'] = $this->mode ?? $preset['mode'] ?? static::MODE_ORIGINAL;
      $preset['fill'] = $this->fill ?? $preset['fill'] ?? null;

      return new MediaPreset($preset);
    }

    throw new InvalidPresetException($this->preset);
  }

  public function
  default()
  {
    $preset = $this->preset();

    if ($src = $this->src()) {
      return media_url($src, $preset->size, $preset->mode, $preset->fill);
    }

    $size = $preset->size === '0x0' ? '256x256' : $preset->size;

    return 'https://placehold.it/' . $size;
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
        'paths' => []
      ];

      foreach ($preset->resolutions as $resolution) {
        $srcSet['paths'][$resolution] = media_url($this->src(), $preset->size, $preset->mode, $preset->fill) . '?src=' . $preset->maxWidth . '&res=' . $resolution;
      }

      $mergedSets = [];
      foreach ($srcSet['paths'] as $resolution => $path) {
        $mergedSets[] = $path . ' ' . $resolution;
      }

      $srcSet['paths'] = implode(', ', $mergedSets);
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
    return view('components.responsive-picture');
  }
}
