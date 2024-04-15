<?php

use Netflex\Pages\Page;
use Netflex\API\Facades\API;
use Netflex\Foundation\Variable;
use Netflex\Foundation\GlobalContent;
use Illuminate\View\ComponentAttributeBag;

use Carbon\Carbon;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Netflex\Newsletters\Newsletter;
use Netflex\Pages\AbstractPage;
use Netflex\Pages\ContentFile;
use Netflex\Pages\ContentImage;
use Netflex\Pages\Extension;
use Netflex\Pages\JwtPayload;
use Netflex\Pages\NavigationData;

use Netflex\Pages\Contracts\MediaUrlResolvable;
use Netflex\Pages\Components\Picture;
use Netflex\Pages\MediaPreset;
use Netflex\Pages\Providers\RouteServiceProvider;

const MODE_ORIGINAL = Picture::MODE_ORIGINAL;
const MODE_FILL = Picture::MODE_FILL;
const MODE_EXACT = Picture::MODE_EXACT;
const MODE_FIT = Picture::MODE_FIT;
const MODE_PORTRAIT = Picture::MODE_PORTRAIT;
const MODE_LANDSCAPE = Picture::MODE_LANDSCAPE;
const MODE_AUTO = Picture::MODE_AUTO;
const MODE_CROP = Picture::MODE_CROP;
const MODE_FIT_DIR = Picture::MODE_FIT_DIRECTION;

const DIR_TOP = Picture::DIRECTION_TOP;
const DIR_TOP_LEFT = Picture::DIRECTION_TOP_LEFT;
const DIR_TOP_RIGHT = Picture::DIRECTION_TOP_RIGHT;
const DIR_BOTTOM = Picture::DIRECTION_BOTTOM;
const DIR_BOTTOM_LEFT = Picture::DIRECTION_BOTTOM_LEFT;
const DIR_BOTTOM_RIGHT = Picture::DIRECTION_BOTTOM_RIGHT;
const DIR_LEFT = Picture::DIRECTION_LEFT;
const DIR_RIGHT = Picture::DIRECTION_RIGHT;
const DIR_CENTER = Picture::DIRECTION_CENTER;

const MEDIA_PRESET_ORIGINAL = MediaPreset::ORIGINAL;

const PAGE_DOMAIN = Page::TYPE_DOMAIN;
const PAGE_EXTERNAL = Page::TYPE_EXTERNAL;
const PAGE_INTERNAL = Page::TYPE_INTERNAL;
const PAGE_FOLDER = Page::TYPE_FOLDER;
const PAGE = Page::TYPE_PAGE;

if (!function_exists('render_component_tag')) {
  /**
   * Renders a component tag, using its class if available
   *
   * @param string $component
   * @param ComponentAttributeBag|array $variables
   * @param HtmlString|string|null $slot
   * @return string|null
   */
  function render_component_tag($component, $variables = [], $slot = null)
  {
    // Normalize component name
    $component = Str::kebab($component);

    if (!Str::startsWith($component, 'x-')) {
      $component = 'x-' . $component;
    }

    $component = str_replace('/', '.', $component);

    // Normalize to array in case of ComponentAttributeBag
    $variables = collect($variables)->toArray();

    try {
      $attributes = implode(' ', array_map(
        function ($key) {
          return sprintf(
            ':%s="%s"',
            $key,
            'app()->get(\'__attribute_' . blockhash_append($key) . '\')'
          );
        },
        array_keys($variables)
      ));

      array_map(function ($key) use ($variables) {
        app()->bind('__attribute_' . blockhash_append($key), function () use ($key, $variables) {
          return $variables[$key];
        });
      }, array_keys($variables));

      $slotBinding = 'app()->get(\'__attribute_' . blockhash_append('slot') . '\')';
      app()->bind('__attribute_' . blockhash_append('slot'), function () use ($slot) {
        return $slot;
      });

      $compiled = Blade::compileString("<$component $attributes>{!! $slotBinding !!}</$component>");

      return str_replace('<?php', '', str_replace('?>', '', $compiled));
    } catch (InvalidArgumentException $e) {
      throw $e;
    } catch (Exception $e) {
      return null;
    }
  }
}

if (!function_exists('jwt_payload')) {
  /**
   * @return JwtPayload|null
   */
  function jwt_payload()
  {
    if (App::has('JwtPayload')) {
      return App::get('JwtPayload');
    }
  }
}

if (!function_exists('register_extension')) {
  /**
   * @param string $alias
   * @param string $extension
   * @return void
   */
  function register_extension($alias, $extension)
  {
    return Extension::register($alias, $extension);
  }
}

if (!function_exists('resolve_extension')) {
  /**
   * @param string $alias
   * @param array $data
   * @return Extension|null
   */
  function resolve_extension($alias, $data = [])
  {
    return Extension::resolve($alias, $data);
  }
}

if (!function_exists('static_content')) {
  /**
   * @param string $block
   * @param string $area
   * @param string $column
   * @return HtmlString|string
   */
  function static_content($block, $area = null, $column = null)
  {
    if ($content = GlobalContent::retrieve($block)) {
      $staticContent = $content->globals
        ->filter(function ($item) use ($area) {
          if ($area) {
            return $item->alias === $area;
          }

          return true;
        })
        ->map(function ($item) use ($column) {
          $column = $column ?? $item->content_type;
          return $item->content->{$column} ?? null;
        })
        ->filter()
        ->reduce(function ($value, $item) {
          return $item . $value;
        }, '');

      if ($staticContent !== strip_tags($staticContent)) {
        $staticContent = new HtmlString($staticContent);
      }

      return $staticContent;
    }
  }
}

if (!function_exists('navigation_data')) {
  /**
   * Resolves navigation data
   *
   * @param int $parent
   * @param string $type
   * @param string $root
   * @return Collection
   */
  function navigation_data($parent = null, $type = 'nav', $root = null)
  {
    return NavigationData::get($parent, $type, $root);
  }
}

if (!function_exists('if_mode')) {
  /**
   * Determines if the app is in one or more modes
   *
   * @param string|string[] ...$modes
   * @return bool
   */
  function if_mode(...$modes)
  {
    $modes = Collection::make([$modes])
      ->flatten()
      ->toArray();

    if (in_array(current_mode(), $modes)) {
      return true;
    }

    return false;
  }
}

if (!function_exists('editor_tools')) {
  /**
   * Gets or sets the current blockhash
   *
   * @param string $value
   * @return string|null|void
   */
  function editor_tools(...$args)
  {
    if (!count($args)) {
      if (App::has('__editor_tools__')) {
        return App::get('__editor_tools__');
      }

      return null;
    }

    $value = array_shift($args) ?? null;

    App::bind('__editor_tools__', function () use ($value) {
      return $value;
    });

    return $value;
  }
}

if (!function_exists('current_revision')) {
  /**
   * @return int|null
   */
  function current_revision()
  {
    if ($page = current_page()) {
      return $page->revision;
    }
  }
}

if (!function_exists('route_hash')) {
  /**
   * @param Route $route
   * @return string
   */
  function route_hash(Route $route)
  {
    return 'route.' . md5(spl_object_hash($route));
  }
}

if (!function_exists('insert_content_if_not_exists')) {
  /**
   * Inserts content if it doesnt exists
   * returns the content
   *
   * @param string $area
   * @param array $content
   * @return object
   */
  function insert_content_if_not_exists($alias, $type, $default = null)
  {
    if ($page = current_page()) {
      $content = $page->content->first(function ($content) use ($alias) {
        return $content->area === $alias;
      });

      if ($content) {
        return $content;
      }

      $payload = [
        'relation' => 'page',
        'relation_id' => $page->id,
        'revision' => current_revision(),
        'published' => true,
        'area' => $alias,
        'type' => $type,
      ];

      if ($default && $type) {
        $payload[$type] = $default;
      }

      $contentId = API::post('builder/content', $payload)->content_id;

      Cache::forget('page');
      Cache::forget('page/' . $page->id);

      return API::get("builder/content/$contentId");
    }
  }
}

if (!function_exists('blocks')) {
  /**
   * @param string $area
   * @return Collection
   */
  function blocks($area)
  {
    if ($page = current_page()) {
      return $page->getBlocks($area)->map(function ($block) {
        return [
          config("blocks.overrides.{$block[0]}", $block[0]),
          $block[1],
        ];
      });;
    }

    return collect([]);
  }
}

if (!function_exists('map_content')) {
  /**
   * Maps the content into useable data
   *
   * @param array $content
   * @param array $settings
   * @param string $field
   * @return mixed
   */
  function map_content($content, $settings, $field = 'auto', $options = [])
  {
    $content = collect($content);

    if ($field === null) {
      return $content->shift();
    }

    switch ($settings['type']) {
      case 'contentlist':
        return $content->mapWithKeys(function ($item) {
          return [$item->title => (object) [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description
          ]];
        });
      case 'contentlist_advanced':
        return $content->mapWithKeys(function ($item) {
          $hash = $item->title;
          unset($item->title);
          return [$hash => $item];
        });
      case 'entries':
        $page_editable = page_first_editable($settings['alias']);

        $models = Collection::make([
          'Netflex\\Structure\\Entry'
        ]);

        if (isset($page_editable['config']['model'])) {
          $models = Collection::make($page_editable['config']['model']);
        }

        $entryIds = $content->sort(function ($a, $b) {
          return ((int) $b->sorting ?? null) - ((int) $a->sorting ?? null);
        })
          ->reverse()
          ->values()
          ->map(function ($item) {
            return (int) $item->text;
          })
          ->toArray();

        $entries = $models->map(function ($model) use ($entryIds) {
          return call_user_func(array($model, 'find'), $entryIds);
        })
          ->flatten()
          ->filter()
          ->sortBy(function ($item) use ($entryIds) {
            return array_search($item->getKey(), $entryIds);
          })->values();

        return $entries;
      case 'gallery':
        return $content->mapWithKeys(function ($item) {
          $hash = $item->text;
          unset($item->text);
          return [
            $hash => new ContentImage([
              'id' => $item->id,
              'title' => $item->title,
              'description' => $item->description,
              'link' => $item->name,
              'path' => $item->image,
              'file' => (int) $item->file
            ])
          ];
        });
      case 'editor-small':
      case 'editor-large':
        if ($item = $content->shift()) {
          return new HtmlString($item->html ?? '');
        }

        return null;
      case 'textarea':
        if ($item = $content->shift()) {
          return $item->html ?? null;
        }

        return null;
      case 'text':
        if ($item = $content->shift()) {
          return $item->text ?? null;
        }

        return null;
      case 'select':
      case 'color':
        if ($item = $content->shift()) {
          return $item->text ?? '';
        }

        return null;
      case 'checkbox':
        if ($item = $content->shift()) {
          return (bool) $item->text ?? '';
        }

        return false;
      case 'checkbox-group':
      case 'multiselect':
      case 'tags':
        if ($item = $content->shift()) {
          return Collection::make(explode(',', $item->text ?? ''))
            ->filter()
            ->values();
        }

        return Collection::make();
      case 'integer':
        if ($item = $content->shift()) {
          return (int) $item->text ?? '';
        }

        return null;
      case 'datetime':
        if ($item = $content->shift()) {
          try {
            return Carbon::parse($item->text ?? 0);
          } catch (Exception $e) {
            return null;
          }
        }

        return null;
      case 'image':
        if ($item = $content->shift()) {
          return ContentImage::cast([
            'id' => $item->id ?? null,
            'path' => $item->image ?? null,
            'file' => $item->file ?? null,
            'title' => $item->name ?? null,
            'description' => $item->description ?? null
          ]);
        }

        return null;
      case 'file':
        if ($item = $content->shift()) {
          return ContentFile::cast([
            'id' => $item->id ?? null,
            'path' => $item->file ?? null,
            'file' => $item->text ?? null,
            'title' => $item->name ?? null,
            'description' => $item->description ?? null
          ]);
        }

        return null;
      case 'nav':
        if ($item = $content->shift()) {
          return (object) [
            'parent' => Page::model()::find($item->text),
            'levels' => (int) $item->title
          ];
        }

        return null;
      case 'link':
        if ($item = $content->shift()) {
          return (object) [
            'url' => $item->text,
            'target' => $item->description
          ];
        }

        return null;
      default:
        if ($item = $content->shift()) {
          if ($field !== 'auto') {
            return $item->{$field} ?? null;
          }

          return $item->{$settings['type']} ?? null;
        }
    }
  }
}

if (!function_exists('content')) {
  /**
   * @param string $alias
   * @param string $field = 'auto'
   * @param array $options = []
   */
  function content($alias, $field = 'auto', $options = [])
  {
    $settings = page_first_editable($alias);

    if (!$settings) {
      $settings = ['alias' => $alias, 'type' => 'text'];
    }

    if ($page = current_page()) {
      $content = $page->content->filter(function ($content) use ($settings) {
        return $content->area === $settings['alias'];
      });

      $blockContent = $page->content->filter(function ($content) use ($settings) {
        return $content->area === blockhash_append($settings['alias']);
      });

      $content = $blockContent->count() ? $blockContent : $content;

      $content = $content->filter(function ($item) {
        return $item->published;
      });

      /* if ($alias === 'events') {
        dd($content);
      } */

      if ($field !== 'auto') {
        $settings['type'] = $field;
        return map_content($content, $settings, $field, $options);
      }

      return map_content($content, $settings, 'auto', $options);
    }
  }
}


if (!function_exists('page_editable')) {
  /**
   * Gets or sets the current page editable configuration
   *
   * @param array $value
   * @return array
   */
  function page_editable(...$args)
  {
    if ($page = current_page()) {
      $binding = '__' . rtrim('page_editable:' . $page->id, ':') . '__';

      if (!count($args)) {
        if (App::has($binding)) {
          return App::get($binding);
        }

        return [];
      }

      $value = array_shift($args) ?? [];

      App::bind($binding, function () use ($value) {
        return $value;
      });

      return $value;
    }

    return [];
  }
}

if (!function_exists('page_first_editable')) {
  /**
   * Retrieves the first editable configuration
   *
   * @param string $alias
   * @return array|null
   */
  function page_first_editable(string $alias)
  {
    $editable = array_filter(page_editable(), function ($editable) use ($alias) {
      return $editable['alias'] === blockhash_append($alias);
    });

    if ($editable) {
      return array_shift($editable);
    }

    $editable = array_filter(page_editable(), function ($editable) use ($alias) {
      return $editable['alias'] === $alias;
    });

    if ($editable) {
      return array_shift($editable);
    }

    return page_editable_push($alias, 'html');
  }
}

if (!function_exists('page_editable_push')) {
  /**
   * Pushes editable config
   *
   * @param string $alias
   * @param array|string $editable
   * @return array
   */
  function page_editable_push(string $alias, $editable = null, $config = [])
  {
    if (!$editable) {
      throw new Exception('Type or settings are required to bind editable');
    }

    if (!is_array($editable)) {
      $editable = ['type' => $editable];
    }

    if (!array_key_exists('type', $editable)) {
      $editable['type'] = 'text';
    }

    $value = $editable ?? [];
    $value['config'] = $config;
    $value['alias'] = blockhash_append($alias);

    page_editable(
      array_filter(
        array_merge(
          page_editable(),
          [$value]
        )
      )
    );

    return $value;
  }
}

if (!function_exists('blockhash_append')) {
  /**
   * @param string $alias
   * @return string
   */
  function blockhash_append($alias)
  {
    return trim($alias . '_' . blockhash(), '_');
  }
}

if (!function_exists('blockhash')) {
  /**
   * Gets or sets the current blockhash
   *
   * @param string $value
   * @return string|null|void
   */
  function blockhash(...$args)
  {
    if (!count($args)) {
      if (App::has('__blockhash__')) {
        return App::get('__blockhash__');
      }

      return null;
    }

    $value = array_shift($args) ?? null;

    if ($value && !is_string($value)) {
      $frame = debug_backtrace()[0];
      $type = is_object($frame['args'][0]) ? get_class($frame['args'][0]) : gettype($frame['args'][0]);
      throw new TypeError('Argument 1 passed to ' . $frame['function'] . '() must be of type string, ' . $type . ' given on line ' . $frame['line']);
    }

    App::bind('__blockhash__', function () use ($value) {
      return $value;
    });

    return $value;
  }
}

if (!function_exists('is_valid_domain_name')) {
  /**
   * Checks if the given parameter is a valid domain name
   *
   * @param string $domain_name
   * @return bool
   */
  function is_valid_domain_name($domain_name)
  {
    return (preg_match('/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i', $domain_name)
      && preg_match('/^.{1,253}$/', $domain_name)
      && preg_match('/^[^\.]{1,63}(\.[^\.]{1,63})*$/', $domain_name));
  }
}

if (!function_exists('current_domain')) {
  /**
   * Gets or sets the current domain
   *
   * @param string $domain
   * @return string|null
   */
  function current_domain(...$args)
  {
    if (!count($args)) {
      $domain = current_page() ? current_page()->domain : null;

      if (!$domain && App::has('__current_domain__')) {
        $domain = App::get('__current_domain__');
      }

      return $domain && is_valid_domain_name($domain) ? $domain : null;
    }

    $value = array_shift($args) ?? current_page()->domain;

    if ($value && !is_string($value)) {
      $frame = debug_backtrace()[0];
      $type = is_object($frame['args'][0]) ? get_class($frame['args'][0]) : gettype($frame['args'][0]);
      throw new TypeError('Argument 1 passed to ' . $frame['function'] . '() must be of type string, ' . $type . ' given on line ' . $frame['line']);
    }


    App::bind('__current_domain__', function () use ($value) {
      return $value;
    });

    return $value;
  }
}

if (!function_exists('current_page')) {
  /**
   * Gets or sets the current page
   *
   * @param Page $value
   * @return Page|null|void
   */
  function current_page(...$args)
  {
    if (!count($args)) {
      if (App::has('__current_page__')) {
        return App::get('__current_page__');
      }

      return null;
    }

    $value = array_shift($args) ?? null;

    if ($value && !($value instanceof AbstractPage)) {
      $frame = debug_backtrace()[0];
      $type = is_object($frame['args'][0]) ? get_class($frame['args'][0]) : gettype($frame['args'][0]);
      throw new TypeError('Argument 1 passed to ' . $frame['function'] . '() must be an instance of Netflex\Pages\AbstractPage, ' . $type . ' given on line ' . $frame['line']);
    }

    App::bind('__current_page__', fn () => $value);

    return $value;
  }
}

if (!function_exists('current_newsletter')) {
  /**
   * Gets or sets the current newsletter
   *
   * @param Newsletter $value
   * @return Newsletter|null|void
   */
  function current_newsletter(...$args)
  {
    if (!count($args)) {
      if (App::has('__current_newsletter__')) {
        return App::get('__current_newsletter__');
      }

      return null;
    }

    $value = array_shift($args) ?? null;

    if ($value && !($value instanceof Newsletter)) {
      $frame = debug_backtrace()[0];
      $type = is_object($frame['args'][0]) ? get_class($frame['args'][0]) : gettype($frame['args'][0]);
      throw new TypeError('Argument 1 passed to ' . $frame['function'] . '() must be an instance of Netflex\Newsletters\Newsletter, ' . $type . ' given on line ' . $frame['line']);
    }

    App::bind('__current_newsletter__', function () use ($value) {
      return $value;
    });

    return $value;
  }
}

if (!function_exists('current_mode')) {
  /**
   * Gets or sets the current blockhash
   *
   * @param string $value
   * @return string|null|void
   */
  function current_mode(...$args)
  {
    if (!count($args)) {
      if (App::has('__mode__')) {
        return App::get('__mode__');
      }

      return 'live';
    }

    $value = array_shift($args) ?? null;

    if ($value && !is_string($value)) {
      $frame = debug_backtrace()[0];
      $type = is_object($frame['args'][0]) ? get_class($frame['args'][0]) : gettype($frame['args'][0]);
      throw new TypeError('Argument 1 passed to ' . $frame['function'] . '() must be of type string, ' . $type . ' given on line ' . $frame['line']);
    }

    App::bind('__mode__', function () use ($value) {
      return $value;
    });

    return $value;
  }
}

if (!function_exists('cdn_url')) {
  /**
   * Generates a CDN url with optional path appended
   *
   * @param MediaUrlResolvable|string|null $path
   * @param string|null $cdn
   * @return string
   */
  function cdn_url($path = null, $cdn = null)
  {
    $schema = Variable::get('site_cdn_protocol');
    $cdn = $cdn ?? MediaPreset::defaultCdn() ?? Variable::get('site_cdn_url');

    if ($path instanceof MediaUrlResolvable) {
      $path = $path->getPathAttribute();
    }

    return trim((rtrim("$schema://$cdn", '/') . '/' . trim($path, '/')), '/');
  }
}

if (!function_exists('media_url')) {
  /**
   * Get URL to a CDN image
   *
   * @param MediaUrlResolvable|object|array|string $file
   * @param array|string|int|MediaPreset $presetOrSize
   * @param string $type
   * @param array|string|int $color
   * @param string|null $direction
   * @param array $query
   * @return string
   */
  function media_url(
    $file,
    $presetOrSize = null,
    $type = 'rc',
    $color = '255,255,255,1',
    $direction = null,
    array $query = [],
    $cdn = null,
    $ext = null
  ) {
    if ($file instanceof MediaUrlResolvable) {
      $file = $file->getPathAttribute();
    } else {
      if (is_array($file) || is_object($file)) {
        $fallback = (is_object($file) && method_exists($file, '__toString')) ? (string) $file : null;
        $file = data_get($file, 'path', $fallback);
      }
    }

    $size = $presetOrSize;
    $preset = ($presetOrSize instanceof MediaPreset)
      ? $presetOrSize
      : MediaPreset::find($presetOrSize);

    $ext = $ext ?? $preset->ext ?? null;

    if ($preset) {
      $cdn = $preset->cdn ?? $cdn;
      $size = $preset->size ?? null;
      $type = $preset->mode ?? $type;
      $color = $preset->fill ?? $color;
      $direction = $preset->direction ?? $direction;
    }

    if (!$size && !$type && empty($gb)) {
      return cdn_url($file, $cdn);
    }

    $size = (is_string($size) && !(strpos($size, 'x') > 0)) ? "{$size}x{$size}" : $size;
    $size = is_float($size) ? floor($size) : $size;
    $size = is_int($size) ? "{$size}x{$size}" : $size;

    $width = is_array($size) ? floor(($size[0] ?? 0)) : 0;
    $height = is_array($size) ? floor(($size[1] ?? 0)) : 0;
    $size = is_array($size) ? "{$width}x{$height}" : $size;

    if ($direction && $type === 'rc') {
      $type = 'rcf';
    }

    $options = null;

    if ($type === 'fill') {
      if (is_string($color)) {
        $color = explode(',', $color);
      }

      if (is_int($color) || is_float($color)) {
        $color = floor($color % 256);
        $color = "$color,$color,$color,1";
      }

      if (is_array($color)) {
        $r = floor((intval($color[0] ?? 0)) % 256);
        $g = floor((intval($color[1] ?? 0)) % 256);
        $b = floor((intval($color[2] ?? 0)) % 256);
        $a = floatval($color[3] ?? 1.0);
        $color = "$r,$g,$b,$a";
      }

      $options = $color . "/";
    }

    if ($type === 'rcf') {
      $options = $direction . '/';
    }

    $size = $type === 'o' ?  null : "$size/";

    $defaultPreset = MediaPreset::getDefaultPreset();

    if ($defaultPreset->compressor) {
      $query['compressor'] = $defaultPreset->compressor;
    }

    if ($ext !== null) {
      $query['ext'] = $ext;
    }

    $queryString = count($query) > 0 ? ('?' . http_build_query($query)) : '';

    return cdn_url("/media/{$type}/{$size}{$options}{$file}{$queryString}", $cdn);
  }
}

if (!function_exists('in_production')) {
  /**
   * Check if the application is running in a production environment
   *
   * @return bool
   */
  function in_production()
  {
    return App::isProduction();
  }
}

if (!function_exists('in_development')) {
  /**
   * Check if the application is running in a development environment
   *
   * @return bool
   */
  function in_development()
  {
    return App::isLocal();
  }
}

if (!function_exists('edit_mode')) {
  /**
   * Check if the current mode is edit
   *
   * @return bool
   */
  function edit_mode()
  {
    return current_mode() === 'edit';
  }
}

if (!function_exists('preview_mode')) {
  /**
   * Check if the current mode is preview
   *
   * @return bool
   */
  function preview_mode()
  {
    return current_mode() === 'preview';
  }
}

if (!function_exists('live_mode')) {
  /**
   * Check if the current mode is live
   *
   * @return bool
   */
  function live_mode()
  {
    return current_mode() === 'live';
  }
}

if (!function_exists('page_route')) {
  /**
   * Generate the URL to a named route (context aware if in a page scope).
   *
   * @param array|string $name
   * @param mixed $parameters
   * @param bool $absolute
   */
  function page_route($name, $parameters = [], $absolute = true)
  {
    if ($page = current_page()) {
      return $page->route($name, $parameters, $absolute);
    }

    return route($name, $parameters, $absolute);
  }
}

if (!function_exists('clear_route_cache')) {
  function clear_route_cache()
  {
    return Cache::forget(RouteServiceProvider::ROUTE_CACHE);
  }
}
