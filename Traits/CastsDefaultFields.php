<?php

namespace Netflex\Pages\Traits;

use Netflex\Pages\Page;

use Illuminate\Support\Collection;
use Netflex\Newsletters\NewsletterPage;

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
    if (($this->attributes['type'] ?? null) === 'newsletter') {
      return NewsletterPage::TYPE_NEWSLETTER;
    }

    if (!isset($this->attributes['template'])) {
      return Page::TYPE_PAGE;
    }

    switch ($this->attributes['template']) {
      case 'd':
        return Page::TYPE_DOMAIN;
      case 'e':
        return Page::TYPE_EXTERNAL;
      case 'i':
        return Page::TYPE_INTERNAL;
      case 'f':
        return Page::TYPE_FOLDER;
      default:
        return Page::TYPE_PAGE;
    }
  }

  /**
   * @param string $url
   * @return string
   */
  public function getUrlAttribute($url)
  {
    switch ($this->type) {
      case Page::TYPE_DOMAIN:
        return null;
      case Page::TYPE_EXTERNAL:
        return $url;
      case Page::TYPE_INTERNAL:
        return static::find($url)->url ?? '#';
      case Page::TYPE_FOLDER:
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
