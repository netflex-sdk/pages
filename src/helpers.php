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
use Netflex\Pages\Extension;
use Netflex\Pages\JwtPayload;

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
   * @param string $area
   * @param string $block
   * @param string $column
   * @return HtmlString
   */
  function static_content($area, $block = null, $column = null)
  {
    if ($content = GlobalContent::retrieve($area)) {
      return new HtmlString($content->globals
        ->filter(function ($item) use ($block) {
          if ($block) {
            return $item->alias === $block;
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
        }, ''));
    }
  }
}

if (!function_exists('navigation_data')) {
  /**
   * @param int $parent
   * @param string $type
   * @param string $root
   * @return object
   */
  function navigation_data($parent = null, $type = 'nav', $root = null)
  {
    try {
      $pages = $parent ? Page::find($parent)->children : Page::where('published', true)
        ->where(function ($query) {
          return $query->where('parent_id', null)
            ->orWhere('parent_id', 0);
        })->get();

      $mapPage = function (Page $page) use ($root, $type) {
        $target = '';
        $url = $page->url;

        switch ($page->type) {
          case Page::TEMPLATE_EXTERNAL:
            $target = '_blank';
            $url = $root . $target;
            break;
          case Page::TEMPLATE_FOLDER:
            break;
          default:
            break;
        }

        return (object) [
          'id' => $page->id,
          'url' => $url,
          'target' => $target,
          'type' => $page->type,
          'title' => $page->nav_title ? $page->nav_title : $page->name,
          'children' => navigation_data($page->id, $type, $root)
        ];
      };

      return $pages->filter(function (Page $page) use ($type) {
        return (bool) $page->{'visible_' . $type};
      })->map($mapPage);
    } catch (Exception $e) {
      throw $e;
      return Collection::make();
    }
  }
}

if (!function_exists('if_mode')) {
  /**
   * Determines if the app is in one or more modes
   *
   * @param string|string[] ...$modes
   * @return void
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
  function current_revision()
  {
    return current_page() ? current_page()->revision : null;
  }
}

if (!function_exists('route_hash')) {
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
   * @return array
   */
  function insert_content_if_not_exists($alias, $type)
  {
    $page = current_page();
    $content = $page->content->first(function ($content) use ($alias) {
      return $content->area === $alias;
    });

    if ($content) {
      return $content;
    }

    $contentId = API::post('builder/content', [
      'relation' => 'page',
      'relation_id' => $page->id,
      'revision' => current_revision(),
      'published' => true,
      'area' => $alias,
      'type' => $type,
    ])->content_id;

    Cache::forget('page');
    Cache::forget('page/' . $page->id);

    return API::get("builder/content/$contentId");
  }
}

if (!function_exists('blocks')) {
  function blocks($area, $variables = [])
  {
    return current_page()
      ->getBlocks($area, $variables);
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
  function map_content($content, $settings, $field = 'auto')
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

        $entryIds = $content->sort(function ($a, $b) {
          return ((int) $b->sorting ?? null) - ((int) $a->sorting ?? null);
        })->reverse()
          ->values()->map(function ($item) {
            return (int) $item->text;
          })->toArray();

        if (isset($page_editable['config']['model'])) {
          $entries = Collection::make($page_editable['config']['model'])->map(function ($model) use ($entryIds) {
            return call_user_func(array($model, 'find'), $entryIds);
          })
            ->flatten()
            ->filter()
            ->sortBy(function ($item) use ($entryIds) {
              return array_search($item->getKey(), $entryIds);
            })->values();
        };

        return $entries;
      case 'gallery':
        return $content->mapWithKeys(function ($item) {
          $hash = $item->text;
          unset($item->text);
          return [$hash => (object) [
            'id' => $item->id,
            'title' => $item->title,
            'description' => $item->description,
            'path' => $item->image,
            'file' => (int) $item->file
          ]];
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
          return $item->html ?? null;
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
          return (object) [
            'id' => $item->id ?? null,
            'path' => $item->image ?? null,
            'file' => $item->file ?? null,
            'title' => $item->name ?? null,
            'description' => $item->description ?? null
          ];
        }

        return null;
      case 'file':
        if ($item = $content->shift()) {
          return (object) [
            'id' => $item->id ?? null,
            'path' => $item->file ?? null,
            'file' => $item->text ?? null,
            'title' => $item->name ?? null,
            'description' => $item->description ?? null
          ];
        }

        return null;
      case 'nav':
        if ($item = $content->shift()) {
          return (object) [
            'parent' => Page::find($item->text),
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
   */
  function content($alias, $field = 'auto')
  {
    $settings = page_first_editable($alias);

    if (!$settings) {
      $settings = ['alias' => blockhash_append($alias), 'type' => 'text'];
    }

    $page = current_page();

    if ($page) {
      $content = $page->content->filter(function ($content) use ($settings) {
        return $content->area === $settings['alias'];
      });

      $content = $content->filter(function ($item) {
        return $item->published;
      });

      if ($field !== 'auto') {
        $settings['type'] = $field;
        return map_content($content, $settings, $field);
      }

      return map_content($content, $settings);
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
    $page = current_page() ? current_page()->id : null;
    $binding = '__' . rtrim('page_editable:' . $page, ':') . '__';

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
      if (App::has('__current_domain__')) {
        $domain = App::get('__current_domain__');
      }

      return $domain && is_valid_domain_name($domain) ? $domain : null;
    }

    $value = array_shift($args) ?? current_page()->domain;

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

    App::bind('__current_page__', function () use ($value) {
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

    App::bind('__mode__', function () use ($value) {
      return $value;
    });

    return $value;
  }
}

if (!function_exists('media_url')) {
  /**
   * Get URL to a CDN image
   *
   * @param File|string $file
   * @param array|string|int $size
   * @param string $type
   * @param array|string|int $color
   * @return string
   */
  function media_url($file, $size = null, $type = 'rc', $color = '0,0,0', ...$gb)
  {
    if (is_object($file)) {
      $file = $file->path ?? null;
    }

    $schema = Variable::get('site_cdn_protocol');
    $cdn = Variable::get('site_cdn_url');

    if (!$size && !$type && empty($gb)) {
      return "$schema://$cdn/$file";
    }

    $size = (is_string($size) && !(strpos($size, 'x') > 0)) ? "{$size}x{$size}" : $size;
    $size = is_float($size) ? floor($size) : $size;
    $size = is_int($size) ? "{$size}x{$size}" : $size;

    $width = is_array($size) ? floor(($size[0] ?? 0)) : 0;
    $height = is_array($size) ? floor(($size[1] ?? 0)) : 0;
    $size = is_array($size) ? "{$width}x{$height}" : $size;

    $fill = null;

    if ($type === 'fill') {
      if ((is_string($color) || (is_int($color) || is_float($color))) && count($gb)) {
        $color = [$color, $gb[0] ?? 0, $gb[1] ?? 0];
      }

      if (is_string($color)) {
        $color = explode(',', $color);
      }

      if (is_int($color) || is_float($color)) {
        $color = floor($color % 256);
        $color = "$color,$color,$color";
      }

      if (is_array($color)) {
        $r = floor((intval($color[0] ?? 0)) % 256);
        $g = floor((intval($color[1] ?? 0)) % 256);
        $b = floor((intval($color[2] ?? 0)) % 256);
        $color = "$r,$g,$b";
      }

      $fill = $color . "/";
    }

    $size = $type === 'o' ?  null : "$size/";

    return "$schema://$cdn/media/$type/{$size}{$fill}{$file}";
  }
}
