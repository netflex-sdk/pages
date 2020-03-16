<?php

use Netflex\Pages\Page;
use Netflex\API\Facades\API;
use Netflex\Foundation\Variable;

use Carbon\Carbon;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

if (!function_exists('navigation_data')) {
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
    return current_page()->renderBlocks($area, $variables);
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
        $entries = $content->map(function ($item) {
          return (int) $item->text;
        });

        if (isset($page_editable['config']['model'])) {
          $entries = Collection::make($page_editable['config']['model'])->map(function ($model) use ($entries) {
            return call_user_func(array($model, 'find'), $entries->toArray());
          })
            ->flatten()
            ->filter();
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
      case 'editor_small':
      case 'editor_large':
      case 'textarea':
        if ($item = $content->shift()) {
          return $item->html ?? '';
        }

        return null;
      case 'text':
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
          return Carbon::parse($item->text ?? 0);
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

if (!function_exists('picture_srcsets')) {
  /**
   * @param array $settings
   * @return object
   */
  function picture_srcsets(array $settings = [])
  {
    $path = $settings['path'] ?? '';
    $resolutions = $settings['resolutions'] ?? ['1x'];
    $resolutions = !is_array($resolutions) ? [$resolutions] : $resolutions;

    $resolutions = array_map(function ($resolution) {
      if (is_int($resolution) || is_float($resolution)) {
        return "{$resolution}x";
      }

      return $resolution;
    }, $resolutions);

    $breakpoints = $settings['breakpoints'] ?? [
      'xxs' => 320,
      'xs' => 480,
      'sm' => 768,
      'md' => 992,
      'lg' => 1200,
      'xl' => 1440,
      'xxl' => 1920
    ];

    $breakpoints = !is_array($breakpoints) ? [] : $breakpoints;
    $breakpoints['xxs'] = $breakpoints['xxs'] ?? $breakpoints['xs'] ?? 480;

    if (!in_array('1x', $resolutions)) {
      array_unshift($resolutions, '1x');
    }

    $srcsets = [];
    foreach ($breakpoints as $breakpoint => $width) {
      $url = implode(', ', array_map(function ($resolution) use ($path, $width) {
        $url = $path . "?src={$width}w";
        return "$url&res=$resolution $resolution";
      }, $resolutions));

      $srcsets[$breakpoint] = (object) [
        'width' => $width,
        'url' => "$url"
      ];
    }

    return $srcsets;
  }
}
