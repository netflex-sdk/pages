<?php

namespace Netflex\Pages;

use JsonSerializable;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Traits\Macroable;

use Netflex\Support\Accessors;
use Netflex\Pages\Components\Picture;
use Netflex\Pages\Exceptions\BreakpointsMissingException;
use Netflex\Pages\Exceptions\InvalidPresetException;

/**
 * @property-read string $mode
 * @property-read string $direction
 * @property-read string $size
 * @property-read string|null $fill
 * @property-read string[] $resolutions
 * @property-read array $breakpoints
 * @property-read int $maxWidth
 * @property-read int $width
 * @property-read int $height
 */
class MediaPreset implements JsonSerializable
{
  use Accessors;
  use Macroable;

  /**
   * @var array
   */
  const ORIGINAL = [
    'mode' => Picture::MODE_ORIGINAL,
    'resolutions' => ['1x', '2x']
  ];

  /** @var array */
  protected $attributes = [];

  public function __construct($preset = [])
  {
    $this->attributes = $preset;
  }

  /**
   * Registers a preset
   *
   * @param string $name
   * @param static|array $preset
   * @return void
   */
  public static function register($name, $preset)
  {
    if (is_array($preset)) {
      /** @var static */
      $preset = new static($preset);
    }

    Config::set("media.presets.$name", $preset);
  }

  /**
   * @param string $name 
   * @return MediaPreset|null 
   */
  public static function find($name)
  {
    $default = Config::get("media.presets.default", [
      'mode' => MODE_FIT,
      'resolutions' => ['1x', '2x', '3x'],
      'direction' => DIR_CENTER
    ]);

    if ($preset = Config::get("media.presets.{$name}")) {
      $preset['size'] = $preset['size'] ?? $default['size'] ?? null;
      $preset['mode'] = $preset['mode'] ?? $default['mode'] ?? MODE_ORIGINAL;
      $preset['fill'] = $preset['fill'] ?? $default['fill'] ?? null;
      $preset['direction'] = $preset['direction'] ?? $default['direction'] ?? DIR_CENTER;
      $preset['resolutions'] = $preset['resolutions'] ?? $default['resolutions'] ?? null;
      $preset['breakpoints'] = $preset['breakpoints'] ?? $default['breakpoints'] ?? null;

      return new MediaPreset($preset);
    }
  }

  /**
   * @param string $name 
   * @return MediaPreset 
   * @throws InvalidPresetException 
   */
  public static function findOrFail($name)
  {
    if ($preset = static::find($name)) {
      return $preset;
    }

    throw new InvalidPresetException($name);
  }

  public function getModeAttribute($mode = null)
  {
    if (!$mode || $this->size === '0x0') {
      return Picture::MODE_ORIGINAL;
    }

    return $mode;
  }

  public function getResolutionsAttribute($resolutions)
  {
    $resolutions = $resolutions ?? ['1x', '2x', '3x'];
    return collect($resolutions)
      ->filter(function ($resolution) {
        return is_string($resolution);
      })
      ->map(function ($resolution) {
        return Str::lower($resolution);
      })
      ->filter(function ($resolution) {
        return Str::endsWith($resolution, 'x');
      })
      ->sort(function ($a, $b) {
        return intval($a) - intval($b);
      })
      ->values()
      ->toArray();
  }

  public function setMaxWidthAttribute($maxWidth)
  {
    $this->attributes['maxWidth'] = $maxWidth;
  }

  /**
   * @param array $values
   * @return array
   * @throws BreakpointsMissingException
   */
  public function getBreakpointsAttribute($values = [])
  {
    if ($values && is_array($values)) {
      foreach ($values as $breakpoint => $value) {
        if (is_string($value)) {
          $values[$breakpoint] = $values[$value] ?? null;
        }
      }

      $values = array_filter($values);

      $values = array_map(function ($value) {
        $value['mode'] = $value['mode'] ?? $this->mode;
        $value['size'] = $value['size'] ?? $this->size;
        $value['resolutions'] = $value['resolutions'] ?? $this->resolutions;
        $value['fill'] = $value['fill'] ?? $this->fill;
        return new static($value);
      }, $values);
    }

    $values = $values ?? [];

    $breakpoints = Config::get('media.breakpoints') ?? [];

    if (empty($breakpoints)) {
      throw new BreakpointsMissingException;
    }

    return collect($breakpoints)
      ->mapWithKeys(function ($maxWidth, $breakpoint) use ($values) {
        $value = $values[$breakpoint] ?? new static($this->attributes);
        $value->maxWidth = $value->maxWidth ? $value->maxWidth : $maxWidth;
        return [$breakpoint => $value];
      });
  }

  public function getFillAttribute($fill = null)
  {
    return is_string($fill) ? $fill : null;
  }

  public function getSizeAttribute($size = null)
  {
    $type = gettype($size);
    switch ($type) {
      case 'string':
        if (Str::contains($size, 'x')) {
          return $size;
        }
        return "{$size}x{$size}";
      case 'integer':
      case 'float':
        $size = intval($size);
        return "{$size}x{$size}";
      case 'array':
        $size = array_values(array_filter($size));
        if (count($size) === 1) {
          return "{$size[0]}x{$size[0]}";
        }
        @list($width, $height) = $size;
        $width = $width ?? 0;
        $height = $height ?? 0;
        return "{$width}x{$height}";
      default:
        return '0x0';
    }
  }

  public function getWidthAttribute()
  {
    return (int) explode('x', $this->size)[0];
  }

  public function getHeightAttribute()
  {
    return (int) explode('x', $this->size)[1];
  }

  public function getMaxWidthAttribute($maxWidth = 0)
  {
    return (int) $maxWidth;
  }

  public function toArray(): array
  {
    return $this->jsonSerialize();
  }

  public function jsonSerialize()
  {
    return [
      'mode' => $this->mode,
      'size' => $this->size,
      'fill' => $this->fill,
      'direction' => $this->direction,
      'maxWidth' => $this->maxWidth,
      'resolutions' => $this->resolutions
    ];
  }
}
