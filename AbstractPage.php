<?php

namespace Netflex\Pages;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Netflex\API\Facades\API;
use Netflex\Foundation\Template;
use Netflex\Pages\Traits\CastsDefaultFields;
use Netflex\Pages\Traits\HidesDefaultFields;
use Netflex\Query\Builder;
use Netflex\Query\QueryableModel;
use Throwable;

/**
 * @property int $id
 * @property int $directory_id
 * @property string $name
 * @property string|null $title
 * @property string $url
 * @property int $revision
 * @property string $created
 * @property string $updated
 * @property bool $published
 * @property string|null $author
 * @property int $userid
 * @property bool $use_time
 * @property string|null $start
 * @property string|null $stop
 * @property array $tags
 * @property bool $public
 * @property mixed $authgroups
 * @property array $variants
 * @property Page|null $master
 * @property string|null $lang
 * @property-read string|null $domain
 * @property-read Page|null $parent
 * @property bool $children_inherits_permission
 * @property Collection $children
 */
abstract class AbstractPage extends QueryableModel implements Responsable
{
  use CastsDefaultFields;
  use HidesDefaultFields;
  use Macroable;

  /**
   * @var string Routing domain
   */
  const TYPE_DOMAIN = 'domain';

  /**
   * @var string An external URL
   */
  const TYPE_EXTERNAL = 'external';

  /**
   * @var string An internal URL
   */
  const TYPE_INTERNAL = 'internal';

  /**
   * @var string A navigation folder
   */
  const TYPE_FOLDER = 'folder';

  /**
   * @var string A regular page
   */
  const TYPE_PAGE = 'page';

  /** @var string Newsletter content page */
  const TYPE_NEWSLETTER = 'newsletter';

  /**
   * Holds all page objects
   * Avoids recursive resolution of pages
   *
   * @var Collection|null
   */
  protected static $allItems = null;

  /**
   * The relation associated with the model.
   *
   * @var string
   */
  protected $relation = 'page';

  /**
   * The resolvable field associated with the model.
   *
   * @var string
   */
  protected $resolvableField = 'url';

  /**
   * Determines the default field to order the query by
   *
   * @var string
   */
  protected $defaultOrderByField = 'sorting';

  /**
   * Determines the default direction to order the query by
   *
   * @var string
   */
  protected $defaultSortDirection = Builder::DIR_ASC;

  /**
   * Indicates if we should automatically publish the model on save.
   *
   * @var bool
   */
  protected $autoPublishes = false;

  /**
   * Indicates if we should respect the models publishing status when retrieving it.
   *
   * @var bool
   */
  protected $respectPublishingStatus = false;

  /**
   * The number of models to return for pagination. Also determines chunk size for LazyCollection
   *
   * @var int
   */
  protected $perPage = -4000;

  /**
   * Indicates if the model should be timestamped.
   *
   * @var bool
   */
  public $timestamps = true;

  /**
   * The attributes that should be mutated to dates.
   *
   * @var array
   */
  protected $dates = [
    'created',
    'updated',
    'start',
    'stop'
  ];

  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = [];

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['*'];

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = ['id', 'parent_id'];

  /**
   * The accessors to append to the model's array form.
   *
   * @var array
   */
  protected $appends = ['type'];

  public function route($name, $parameters = [], $absolute = true)
  {
    $pageRouteName = $this->config->route_name ?? Str::slug($this->name);

    return route(implode('.', [$pageRouteName, Str::slug($name)]), $parameters, $absolute);
  }

  /**
   * Retrieves a record by key
   *
   * @param int|null $relationId
   * @param mixed $key
   * @return array|null
   */
  protected function performRetrieveRequest(?int $relationId = null, mixed $key = null)
  {
    return API::get('builder/pages/' . $key, true);
  }

  /**
   * Inserts a new record, and returns its id
   *
   * @return mixed
   * @property array $attributes
   * @property int|null $relationId
   */
  protected function performInsertRequest(?int $relationId = null, array $attributes = [])
  {
    $response = API::post('builder/pages', $attributes);

    return $response->page_id;
  }

  /**
   * Updates a record
   *
   * @param int|null $relationId
   * @param mixed $key
   * @param array $attributes
   * @return void
   */
  protected function performUpdateRequest(?int $relationId = null, mixed $key = null, array $attributes = [])
  {
    return API::put('builder/pages/' . $key, $attributes);
  }

  /**
   * Deletes a record
   *
   * @param int|null $relationId
   * @param mixed $key
   * @return bool
   */
  protected function performDeleteRequest(?int $relationId = null, mixed $key = null)
  {
    return !!API::delete('builder/pages/' . $key);
  }

  public function getPublicAttribute($public)
  {
    if (!$public) {
      return false;
    }

    $page = $this->parent;

    if ($page) {
      do {
        if (!$page->public && $page->children_inherits_permission) {
          return false;
        }
      } while ($page = $page->parent);
    }

    return true;
  }

  /**
   * Retrieves the component names of the given block
   *
   * @param string $area
   * @return Collection
   */
  public function getBlocks($area)
  {
    $blocks = $this->content->filter(function ($content) use ($area) {
      return $content->published && $content->area === $area;
    })->map(function ($block) {
      if ($template = Template::retrieve((int)$block->text)) {
        return [$template->alias, $block->title ? $block->title : null];
      };
    });

    return $blocks->filter();
  }

  /**
   * @return bool
   */
  public function hasTemplate()
  {
    return (bool)($this->attributes['template'] ?? null)
      && is_numeric($this->attributes['template']);
  }

  public function getMasterAttribute()
  {
    if (!$this->parent) {
      return $this;
    }

    return $this->parent->master ?? null;
  }

  /**
   * Gets the domain name of the page, using its master page as the base
   *
   * @return string|null
   */
  public function getDomainAttribute()
  {
    $master = $this->master;

    if ($master && $master !== $this) {
      if ($master->type === static::TYPE_DOMAIN && preg_match('/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/', $master->name) !== false) {
        return $master->name;
      }
    }
  }

  public function getLangAttribute()
  {
    if (!$this->attributes['lang']) {
      $parent = $this->parent;
      while ($parent && !$parent->lang) {
        $parent = $parent->parent;
      }

      if ($parent) {
        return $parent->lang;
      }

      return App::getLocale();
    }

    return $this->attributes['lang'];
  }

  /**
   * @param int $template
   * @return Template
   */
  public function getTemplateAttribute($template = null)
  {
    if ($this->hasTemplate()) {
      return Template::retrieve((int)$template);
    }
  }

  /**
   * @param string $title
   * @return string
   */
  public function getTitleAttribute($title)
  {
    if ($title) {
      return $title;
    }

    return $this->name;
  }

  /**
   * Create an HTTP response that represents the object.
   *
   * @param \Illuminate\Http\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function toResponse($request)
  {
    $page = current_page();
    $blockhash = blockhash();

    current_page($this);
    blockhash(null);

    $rendered = $this->template ? $this->template->toResponse() : null;

    current_page($page);
    blockhash($blockhash);

    return $rendered;
  }

  /**
   * Gets the parent page
   *
   * @return static|null
   */
  public function getParentAttribute()
  {
    return static::find((int)$this->parent_id);
  }

  /**
   * Gets this page's children
   *
   * @return collection
   */
  public function getChildrenAttribute()
  {
    return static::all()
      ->filter(function ($page) {
        return (int)$page->parent_id === (int)$this->id;
      })
      ->values();
  }

  private static function getPages(): Collection
  {
    if (!static::$allItems) {
      /** @var Collection */
      $data = Cache::rememberForever('pages', function () {
        return collect(API::get('builder/pages/content', true))
          ->sortBy('sorting')
          ->keyBy('id');
      });

      static::$allItems = $data->map(function ($attributes) {
        return (new static)->newFromBuilder($attributes);
      });
    }

    return static::$allItems;
  }

  /**
   * Retrieves all instances
   *
   * @return Collection|LazyCollection
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function all()
  {

    return static::getPages()->values();
  }

  /**
   * Finds an instance by its primary field
   *
   * @param int|string $id
   * @return static|null
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function find($id)
  {
    return static::getPages()[$id] ?? null;
  }


  /**
   * Resolves an instance
   *
   * @param mixed $resolveBy
   * @param string|null $field
   * @return static|Collection|null
   * @throws NotQueryableException If object not queryable
   * @throws QueryException On invalid query
   */
  public static function resolve($resolveBy, $field = null)
  {
    $resolveBy = Collection::make([$resolveBy])
      ->flatten()
      ->toArray();

    foreach ($resolveBy as $value) {
      $value = $value === '/' ? '/index' : $value;
      $resolveBy[] = $value;
      $resolveBy[] = "$value/";
      $resolveBy[] = Str::replaceFirst('/', '', "$value/");
    }

    $resolveBy = array_map('strtolower', array_unique($resolveBy));

    $resolved = static::all()->filter(function (self $page) use ($resolveBy, $field) {
      $key = strtolower($page->{$field ?? $page->resolvableField});
      return in_array($key, $resolveBy);
    });

    return count($resolveBy) === 1 ? $resolved->first() : $resolved;
  }

  /**
   * Loads the given revision
   *
   * @param int $revisionId
   * @return static
   */
  public function loadRevision($revisionId = null)
  {
    if (!$revisionId) {
      return $this;
    }

    try {
      $content = API::get("builder/pages/{$this->getKey()}/content/{$revisionId}", true);
      $this->attributes['revision'] = $revisionId;
      $this->attributes['content'] = $content;
      return $this;
    } catch (Throwable $e) {
      return null;
    }
  }

  /**
   * Retrieves the current matched Page
   *
   * @return static|null
   */
  public static function current()
  {
    return current_page();
  }

  /**
   * Resolves navigation data for this page
   *
   * @param string $type
   * @param string|null $root
   *
   * @return Collection
   */
  public function navigationData($type = 'nav', $root = null)
  {
    return NavigationData::get($this->id, $type, $root);
  }

  /**
   * Traverses the page graph and determines if the page is a subpage of another pzsage.
   *
   * @param Page $page
   * @param Page|null $pointer
   * @return bool
   */
  public function isSubPageOf(Page $page, ?Page $pointer = null)
  {
    if (!$pointer) {
      $pointer = $this;
    }

    if (!$pointer->parent) {
      return false;
    }

    if ($pointer->parent == $page) {
      return true;
    }

    return $this->isSubPageOf($page, $pointer->parent);
  }

  /**
   * Traverses the page graph and determines if the page is a subpage of another pzsage.
   *
   * @param Page $page
   * @param Page|null $pointer
   * @return bool
   */
  public function isParentPageOf(Page $page)
  {
    return $page->isSubPageOf($this);
  }

  /**
   * Resolves the registered Page model class
   *
   * @return static
   */
  public static function model()
  {
    /** @var Page */
    return Config::get('pages.model', Page::class) ?? Page::class;
  }

  /**
   * Gets the config array as a object
   *
   * @param array|null $config
   * @return object
   */
  public function getConfigAttribute($config = [])
  {
    $config = collect($config ?? []);

    return (object)$config->mapWithKeys(function ($config, $key) {
      return [$key => $config['value'] ?? null];
    })->toArray();
  }

  public function getChildrenInheritsPermissionAttribute($children_inherits_permission)
  {
    return (bool)$children_inherits_permission;
  }

  public function getAuthgroupsAttribute($authgroups)
  {
    if ($authgroups) {
      $authgroups = array_map('intval', array_values(array_filter(explode(',', $authgroups))));
    } else {
      $authgroups = [];
    }

    $page = $this->parent;

    if ($page) {
      do {
        if (!$page->public && $page->children_inherits_permission) {
          return array_values(array_unique([...$authgroups, ...$page->authgroups]));
        }
      } while ($page = $page->parent);
    }

    return $authgroups;
  }

  public function trail($root = null, $visited = [])
  {
    $visited[] = $this;

    if ($root != $this->id) {
      if ($parent = $this->parent) {
        if ($parent->parent === null && $parent->visible_nav) {
          return $this->parent->trail($root, $visited);
        }

        if ($parent->visible_subnav) {
          return $this->parent->trail($root, $visited);
        }
      }
    }

    return array_reverse($visited);
  }
}
