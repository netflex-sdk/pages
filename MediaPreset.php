<?php

namespace Netflex\Pages;

use JsonSerializable;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Traits\Macroable;
use Netflex\Foundation\Variable;
use Netflex\Support\Accessors;
use Netflex\Pages\Components\Picture;
use Netflex\Pages\Exceptions\BreakpointsMissingException;
use Netflex\Pages\Exceptions\InvalidPresetException;

/**
 * @property string $cdn
 * @property-read string $mode
 * @property-read string|null $ext
 * @property-read string $direction
 * @property-read string $size
 * @property-read string|null $fill
 * @property-read string[] $resolutions
 * @property-read array $breakpoints
 * @property-read string|null $compressor
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
    'cdn' => 'default',
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

  protected static function cdnDomains(): array
  {
    return collect(Config::get('media.cdn.domains', []))
      ->mapWithKeys(function ($domain, $cdn) {
        return [$cdn => $domain ?? Variable::get('site_cdn_url')];
      })
      ->all();
  }

  protected static function defaultCdnAlias(): string
  {
    return Config::get('media.cdn.default', 'default') ?? 'default';
  }

  public static function defaultCdn(): string
  {
    return static::getCdn(static::defaultCdnAlias());
  }

  protected static function getCdn($alias): string
  {
    if ($cdn = static::resolveCdn($alias)) {
      return $cdn;
    }

    return Variable::get('site_cdn_url');
  }

  public static function resolveCdn($alias): ?string
  {
    if ($alias === 'default') {
      $alias = static::defaultCdnAlias();
    }

    if ($cdn = static::cdnDomains()[$alias] ?? null) {
      return $cdn;
    }

    return null;
  }

  public static function getDefaultPreset()
  {
    $default = Config::get("media.presets.default", [
      'cdn' => static::defaultCdn(),
      'mode' => MODE_FIT,
      'ext' => null,
      'resolutions' => ['1x', '2x', '3x'],
      'direction' => DIR_CENTER,
    ]);

    if (config('media.compressor')) {
      $default['compressor'] ??= config('media.compressor');
    }

    return new MediaPreset($default);
  }

  /**
   * @param string $name
   * @return MediaPreset|null
   */
  public static function find($name): ?self
  {
    $default = static::getDefaultPreset();

    if ($preset = Config::get("media.presets.{$name}")) {
      $preset['cdn'] ??= $default->cdn ?? null;
      $preset['size'] ??= $default->size ?? null;
      $preset['mode'] ??= $default->mode ?? MODE_ORIGINAL;
      $preset['ext'] ??= $default->ext ?? null;
      $preset['fill'] ??= $default->fill ?? null;
      $preset['direction'] ??= $default->direction ?? DIR_CENTER;
      $preset['resolutions'] ??= $default->resolutions ?? null;
      $preset['breakpoints'] ??= $default->breakpoints ?? null;
      $preset['compressor'] ??= $default->compressor ?? null;

      return new MediaPreset($preset);
    }

    return null;
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

  public function getCdnAttribute(?string $cdn): string
  {
    return static::getCdn($cdn);
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
        $value['cdn'] = $value['cdn'] ?? $this->cdn;
        $value['mode'] = $value['mode'] ?? $this->mode;
        $value['ext'] = $value['ext'] ?? $this->ext;
        $value['size'] = $value['size'] ?? $this->size;
        $value['resolutions'] = $value['resolutions'] ?? $this->resolutions;
        $value['compressor'] = $value['compressor'] ?? $this->compressor;
        $value['direction'] = $value['direction'] ?? $this->direction;
        $value['fill'] = $value['fill'] ?? $this->fill;
        return  $value;
      }, $values);
    }

    $values = $values ?? [];

    $breakpoints = Config::get('media.breakpoints') ?? [];

    if (empty($breakpoints)) {
      throw new BreakpointsMissingException;
    }

    return collect($breakpoints)
      ->mapWithKeys(function ($maxWidth, $breakpoint) use ($values) {
        $value = $values[$breakpoint] ?? $this->attributes;
        $value['maxWidth'] ??= $maxWidth;
        return [$breakpoint => new static($value)];
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
      'ext' => $this->ext,
      'size' => $this->size,
      'fill' => $this->fill,
      'direction' => $this->direction,
      'maxWidth' => $this->maxWidth,
      'resolutions' => $this->resolutions,
      'compressor' => $this->compressor,
    ];
  }
}
