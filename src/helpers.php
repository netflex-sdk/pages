<?php

use Netflex\Pages\Page;
use Carbon\Carbon;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Netflex\API\Facades\API;
use Netflex\Foundation\Variable;
use Netflex\Pages\Types\File;
use Netflex\Pages\Types\Image;

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

    API::post('builder/content', [
      'relation' => 'page',
      'relation_id' => $page->id,
      'revision' => $page->revision,
      'published' => true,
      'area' => $alias,
      'type' => $type,
    ]);

    Cache::forget('page');
    Cache::forget('page/' . $page->id);

    $page = current_page($page->fresh());

    return $page->content->first(function ($content) use ($alias) {
      return $content->area === $alias;
    });
  }
}

if (!function_exists('inline')) {
  /**
   * @param string $alias
   * @param string $tag
   * @param string $type
   * @return void
   */
  function inline($alias, $type = 'html', Closure $view, Closure $edit)
  {
    $alias = blockhash_append($alias);

    if (current_mode() !== 'edit') {
      $content = content($alias, $type);
      return $content ? $view($content) : null;
    } else {
      $content = insert_content_if_not_exists($alias, $type);
      return $edit($content);
    }
  }
}

if (!function_exists('inline_text')) {
  /**
   * @param string $alias
   * @param string $tag
   * @param string $class
   * @return string|null
   */
  function inline_text($alias, $tag = null, $class = null)
  {
    return inline($alias, 'html', function ($content) use ($tag, $class) {
      $start = $tag ? "<$tag class=\"$class\">" : null;
      $stop = $tag ? "</$tag>" : null;
      return "{$start}{$content->html}{$stop}";
    }, function ($content) use ($alias, $tag, $class) {
      $tag = $tag ?? 'div';
      return <<<HTML
        <$tag
          id="e-{$content->id}-html"
          class="$class"
          data-content-area="$content->area"
          data-content-type="html"
          data-content-id="{$content->id}"
          contenteditable="true">
          {$content->html}
        </$tag>
HTML;
    });
  }
}

if (!function_exists('inline_picture')) {
  /**
   * @param string $alias
   * @param array ...$args
   * @return string|null
   */
  function inline_picture($alias, $config = [])
  {
    return inline($alias, 'image', function ($content) use ($config) {
      if ($content->image) {
        $config['path'] = $content->image;
        return picture($config);
      }
    }, function ($content) use ($alias, $config) {
      $path = $content->image;
      $picture_class = $config['picture_class'] ?? null;
      $dimensions = $config['dimensions'] ?? '128x128';
      $image_class = $config['image_class'] ?? null;
      $compression = $config['compression'] ?? 'rc';
      $alt = $config['alt'] ?? null;
      $title = $config['title'] ?? null;
      $src = $content->image ?? ('https://placehold.it/' . $dimensions);
      return <<<HTML
        <picture
          id="e-{$content->id}-image"
          class="$picture_class find-image"
          data-content-area="{$content->area}"
          data-content-type="image"
          data-content-dimensions="$dimensions"
          data-content-compressiontype="$compression"
          data-content-id="{$content->id}"
        >
          <source srcset="$src?src=320w" media="(max-width: 320px)">
          <source srcset="$src?src=480w" media="(max-width: 480px)">
          <source srcset="$src?src=768w" media="(max-width: 768px)">
          <source srcset="$src?src=992w" media="(max-width: 992px)">
          <source srcset="$src?src=1200w" media="(max-width: 1200px)">
          <source srcset="$src">
          <img class="$image_class" src="$src" alt="$alt" title="$title" />
        </picture>
HTML;
    });
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
      return $content;
    }

    switch ($settings['type']) {
      case 'entries':
      case 'contentlist':
      case 'contentlist_advanced':
      case 'gallery':
        dd('not implemented');
      case 'editor_small':
      case 'editor_large':
      case 'textarea':
        return $content->shift()->html ?? '';
      case 'text':
        return $content->shift()->text ?? '';
      case 'checkbox':
        return (bool) $content->shift()->text ?? false;
      case 'checkbox_group':
      case 'select':
      case 'multiselect':
        dd('not implemented');
      case 'tags':
        return explode(',', ($content->shift()->text ?? ''));
      case 'integer':
        return ($content->shift()->text ?? 0) + 0;
      case 'datetime':
        return Carbon::parse($content->shift()->text ?? 0);
      case 'image':
        return new Image($content->shift() ?? []);
      case 'file':
        return new File($content->shift() ?? []);
      case 'nav':
      case 'link':
        dd('not implemented');
      default:
        if ($field !== 'auto') {
          return $content->shift()->{$field} ?? null;
        }

        return $content->shift()->{$settings['type']} ?? null;
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

      if ($field !== 'auto') {
        $settings['type'] = $field;
        return map_content($content, $settings);
      }

      return map_content($content, $settings);
    }
  }
}

if (!function_exists('edit_button')) {
  /**
   * @param string $alias
   * @param array $config
   * @return string
   */
  function edit_button($alias, $settings = [])
  {
    if (current_mode() !== 'edit') {
      return null;
    }

    $page_editable = page_first_editable($alias);

    if (!$page_editable) {
      page_editable_push('alias', ['type' => 'text']);
      $page_editable = page_first_editable($alias);
    }

    $settings = array_merge($page_editable, $settings);

    $position = $settings['position'] ?? 'topright';
    $class = "netflex-content-settings-btn netflex-content-btn-pos-$position ";
    $class = trim($class . ($settings['class'] ?? null));
    $name = $settings['name'] ?? $alias ?? null;
    $title = $settings['label'] ?? $name ?? $alias;
    $alias = blockhash_append($alias);
    $maxItems = $settings['max-items'] ?? 99999;
    $style = $settings['style'] ?? null;
    $icon = $settings['icon'] ?? null;
    $icon = $icon ? "<span class=\"{$icon}\"></span>" : null;
    $description = $settings['description'] ?? null;
    $field = $settings['content_field'] ?? null;
    $page = current_page();

    $type = $settings['type'] ?? null;
    $field = 'huh?';

    $config = null;

    if ($settings['config'] ?? false) {
      $config = base64_encode(serialize($settings['config']));
    }

    return <<<HTML
<a href="#"
   class="$class"
   style="$style"
   data-area-name="$name"
   data-area-field="$field"
   data-area-description="$description"
   data-page-id="{$page->id}"
   data-area-config="$config"
   data-area-type="$type"
   data-area-alias="$alias"
   data-max-items="$maxItems"
>$icon $title</a>
HTML;
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
  function page_editable_push(string $alias, $editable = null)
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

    return "$schema://$cdn/media/$type/$size/{$fill}{$file}";
  }
}

if (!function_exists('picture_raw')) {
  /**
   * @param array $settings
   * @return object
   */
  function picture_raw(array $settings = [])
  {
    $path = $settings['path'] ?? '';
    $path = is_object($path) ? $path->path : $path;
    $alt = $settings['alt'] ?? '';
    $title = $settings['title'] ?? '';
    $alt = $alt ?? $title ?? '';
    $title = $title ?? $alt ?? '';
    $size = $settings['dimensions'] ?? '128x128';
    $type = $settings['compression'] ?? 'rc';
    $color = $settings['fill'] ?? '0,0,0';
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
    foreach ($breakpoints as $breakpoint => $maxWidth) {
      $url = implode(', ', array_map(function ($resolution) use ($path, $size, $type, $color, $maxWidth) {
        $url = media_url($path, $size, $type, $color) . "?src={$maxWidth}w";
        return "$url&res=$resolution $resolution";
      }, $resolutions));

      $srcsets[$breakpoint] = (object) [
        'path' => $path,
        'resolutions' => $resolutions,
        'compression' => $type,
        'fill' => $color,
        'dimensions' => $size,
        'maxwidth' => $maxWidth,
        'url' => "$url"
      ];
    }

    return (object) [
      'srcset' => $srcsets,
      'path' => media_url($path, $size, $type, $color)
    ];
  }
}

if (!function_exists('picture')) {
  /**
   * @param array|string $settings|$path
   * @param string? $size
   * @param string? $type
   * @return string
   */
  function picture($settings = [], ...$args)
  {
    if (is_string($settings)) {
      $settings = [
        'path' => $settings,
        'dimensions' => count($args) > 0 ? $args[0] : null,
        'compression' => count($args) > 1 ? $args[1] : null
      ];
    }

    $picture_class = $settings['picture_class'] ?? '';
    $image_class = $settings['image_class'] ?? '';
    $image_style = $settings['image_style'] ?? '';
    $path = $settings['path'] ?? null;
    $path = is_object($path) ? $path->path : $path;
    $size = $settings['dimensions'] ?? '1x1';
    $type = $settings['compression'] ?? 'rc';
    $color = $settings['fill'] ?? '0,0,0';
    $title = $settings['title'] ?? '';
    $alt = $settings['alt'] ?? $title ?? '';
    $title = $title ?? $alt ?? '';
    $resolutions = $settings['resolutions'] ?? null;
    $breakpoints = $settings['breakpoints'] ?? null;

    $src = media_url($path, $size, $type, $color);
    $srcsets = [];

    foreach (picture_raw([
      'path' => $path,
      'dimensions' => $size,
      'compression' => $type,
      'fill' => $color,
      'resolutions' => $resolutions,
      'breakpoints' => $breakpoints
    ])->srcset as $srcset) {
      $srcsets[] = <<<HTML
<source srcset="{$srcset->url}" media="(max-width: {$srcset->maxwidth}px)">
HTML;
    }

    $srcsets = implode("\n", $srcsets);

    return <<<HTML
<picture class="$picture_class">
  {$srcsets}
  <img class="$image_class" src="$src" alt="$alt" title="$title" style="$image_style" />
</picture>
HTML;
  }
}

if (!function_exists('image')) {
  function image(...$args)
  {
    if (is_object($args[0])) {
      $args[0] = (string) $args[0];
    }

    $url = media_url(...$args);

    return <<<HTML
<img src="$url">
HTML;
  }
}

if (!function_exists('nav')) {
  /**
   * Generates a nav
   *
   * @param int $parent
   * @param int $levels
   * @param string $class
   * @param string $type
   * @param string $root
   * @param string $li
   * @param string $a
   * @return string
   */
  function nav($parent = null, $levels = 1, $class = null, $type = 'nav', $root = null, $li = null, $a = null)
  {
    $parent = $parent ? Page::find($parent) : current_page();
    $children = [];
    $route = request()->route();

    if ($parent && $levels > 0) {
      foreach ($parent->children as $child) {
        $children[] = (function ($child) use ($route, $levels, $type, $root, $li, $a) {
          if ($child->{"visible_$type"}) {
            $url = null;
            $target = '_self';
            $title = $child->navtitle ? $child->navtitle : $child->name;
            $classList = [$a];

            if (get_class($route) === Route::class && $route->data('page')->id === $child->id) {
              $classList[] = 'active';
            }

            $url = $child->url;

            if ($child->type === 'folder') {
              $class[] = 'navfolder';
            }

            foreach (['xs', 'sm', 'md', 'lg'] as $breakpoint) {
              if ($child->{"nav_hidden_$breakpoint"}) {
                $classList[] = "hidden-$breakpoint";
              }
            }

            $class = implode(' ', array_filter($classList));

            $childItems = ($levels >= 2)
              ? nav(
                $child->id,
                $levels - 1,
                'dropdown-container',
                $type,
                $root,
                $li,
                $a
              )
              : null;

            return <<<HTML
<li class="$li $class">
  <a href="$url" class="$class" target="$target" role="menuitem">
    $title
  </a>
  $childItems
</li>
HTML;
          }
        })($child);
      }
    }

    $children = implode("\n", array_filter($children));

    return <<<HTML
<ul class="$class" role="menu">
  $children
</ul>
HTML;
  }
}
