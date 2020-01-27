<?php

namespace Netflex\Pages\Traits;

use Netflex\Pages\Page;

use Illuminate\Support\Collection;

trait CastsDefaultFields
{
  public static function bootCastsDefaultFields()
  {
    $defaults = [
      'id' => 'int',
      'group_id' => 'int',
      'children_inherit_url' => 'bool',
      'template' => 'int',
      'visible' => 'bool',
      'visible_nav' => 'bool',
      'visible_subnav' => 'bool',
      'nav_hidden_xs' => 'bool',
      'nav_hidden_sm' => 'bool',
      'nav_hidden_md' => 'bool',
      'nav_hidden_lg' => 'bool',
      'parent_id' => 'int',
      'public' => 'bool',
      'published' => 'bool',
      'content'
    ];

    static::retrieved(function ($model) use ($defaults) {
      $model->casts = array_merge($model->casts, $defaults);
    });

    static::created(function ($model) use ($defaults) {
      $model->casts = array_merge($model->casts, $defaults);
    });
  }

  /**
   * @param array $content
   * @return Collection
   */
  public function getContentAttribute($content = [])
  {
    return Collection::make($content)->map(function ($content) {
      return (object) $content;
    });
  }

  public function getKeywordsAttribute($keywords)
  {
    $keywords = !is_array($keywords) ? explode(',', $keywords) : $keywords;
    return array_values(array_filter($keywords));
  }

  public function setKeywordsAttribute($keywords)
  {
    $keywords = !is_string($keywords) ? implode(',', $keywords) : $keywords;
    $this->attributes[$keywords] = $keywords;
  }

  /**
   * @return string
   */
  public function getTypeAttribute()
  {
    switch ($this->attributes['template']) {
      case Page::TEMPLATE_EXTERNAL:
        return 'external';
      case Page::TEMPLATE_INTERAL:
        return 'internal';
      case Page::TEMPLATE_FOLDER:
        return 'folder';
      default:
        return 'page';
    }
  }

    /**
   * @param string $url
   * @return string
   */
  public function getUrlAttribute($url)
  {
    switch ($this->type) {
      case 'external':
        return $url;
      case 'internal':
        return static::find($url)->url ?? '#';
      case 'f':
        return '#';
      default:
        return '/' . trim($url === 'index/' ? '/' : $url, '/');
    }
  }

  /**
   * @param string $authgroups
   * @return array
   */
  public function getAuthGroupsAttribute($authgroups = '')
  {
    return array_map(
      'intval',
      array_values(
        array_filter(
          explode(',', $authgroups)
        )
      )
    );
  }
}
