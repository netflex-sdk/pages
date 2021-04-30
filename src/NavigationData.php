<?php

namespace Netflex\Pages;

use Exception;
use JsonSerializable;

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
   * @param int $parent
   * @param string $type
   * @param string $root
   * @return Collection
   */
  public static function get($parent = null, $type = 'nav', $root = null)
  {
    try {
      $pages = $parent ? Page::model()::find($parent)->children : Page::model()::where('published', true)
        ->where(function ($query) {
          return $query->where('parent_id', null)
            ->orWhere('parent_id', 0);
        })->get();

      $mapPage = function (Page $page) use ($root, $type) {
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
          'children' => navigation_data($page->id, $type, $root)
        ]);
      };

      return $pages->filter(function (Page $page) use ($type) {
        return (bool) $page->{'visible_' . $type};
      })
        ->map($mapPage)
        ->values();
    } catch (Exception $e) {
      throw $e;
      return Collection::make();
    }
  }
}
