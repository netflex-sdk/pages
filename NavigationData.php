<?php

namespace Netflex\Pages;

use Exception;
use JsonSerializable;

use Netflex\Query\Builder;
use Netflex\Support\Accessors;

use Illuminate\Support\Collection;
use Illuminate\Support\Traits\Macroable;

/**
 * @property int $id
 * @property string|null $url
 * @property string|null $target
 * @property int|string $type
 * @property Collection $children
 * @property Page $page
 * @property Page|null $parent
 * @property bool $active
 */
class NavigationData implements JsonSerializable
{
  use Accessors;
  use Macroable;

  /** @var array */
  protected $attributes = [];

  public function __construct($attributes = [])
  {
    $this->attributes = $attributes;
  }

  public function jsonSerialize()
  {
    return $this->__debugInfo();
  }

  public function __debugInfo()
  {
    return [
      'id' => $this->id,
      'url' => $this->url,
      'target' => $this->target,
      'type' => $this->type,
      'children' => $this->children->toArray(),
      'active' => $this->active
    ];
  }

  /**
   * @param string $target
   * @return string|null
   */
  public function getTargetAttribute($target)
  {
    if ($target) {
      return $target;
    }
  }

  public function getActiveAttribute()
  {
    if ($page = current_page()) {
      return $this->page->id === $page->id;
    }

    return false;
  }

  /**
   * @return Page|null
   */
  public function getPageAttribute()
  {
    if ($page = current_page()) {
      if ($page->id === $this->id) {
        return $page;
      }
    }

    return Page::model()::find($this->id);
  }

  /**
   * Resolves navigation data
   * 
   * @param int|null $parent = null
   * @param string $type = 'nav'
   * @param string|null $root = null
   * @return Collection
   */
  public static function get($parent = null, string $type = 'nav', ?string $root = null)
  {
    return Page::model()::all()
      ->where('published', true)
      ->where('parent_id', $parent)
      ->where('visible_' . $type)
      ->map(function (Page $page) use ($root, $type) {
        $target = $page->nav_target;
        $url = $page->url;

        switch ($page->type) {
          case Page::TYPE_EXTERNAL:
            $target = $target ?? '_blank';
            break;
          case Page::TYPE_FOLDER:
            break;
          default:
            $url = $root . $url;
            break;
        }

        return new static([
          'id' => $page->id,
          'url' => $url,
          'target' => $target,
          'type' => $page->type,
          'title' => $page->nav_title ? $page->nav_title : $page->name,
          'children' => static::get($page->id, $type, $root)
        ]);
      })
      ->values();
  }
}
