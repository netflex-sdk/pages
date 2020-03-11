<?php

namespace Netflex\Pages\Components;

use Illuminate\View\Component;

class Picture extends Component
{
    const MODE_ORIGINAL = 'o';
    const MODE_FILL = 'fill';
    const MODE_EXACT = 'e';
    const MODE_FIT = 'rc';
    const MODE_PORTRAIT = 'p';
    const MODE_LANDSCAPE = 'l';
    const MODE_AUTO = 'a';
    const MODE_CROP = 'c';

    public function __construct(
        $area = null,
        $size = null,
        $width = null,
        $height = null,
        $mode = Picture::MODE_EXACT,
        $color = '0,0,0',
        $src = null,
        $alt = null,
        $title = null,
        $class = null,
        $style = null,
        $imageClass = null,
        $imageStyle = null
    ) {
        $this->settings = (object) [
            'alias' => $area ? blockhash_append($area) : null,
            'size' => $size ?? (($width && $height) ? "{$width}x{$height}" : null),
            'mode' => $mode,
            'color' => $color,
            'src' => $src,
            'alt' => $alt,
            'title' => $title,
            'class' => $class,
            'style' => $style,
            'imageClass' => $imageClass,
            'imageStyle' => $imageStyle,
            'inline' => (bool) ($area ?? false),
            'content' => null
        ];

        if ($this->settings->inline) {
            $area = $this->settings->alias;
            if (current_mode() === 'edit') {
                $this->settings->content = insert_content_if_not_exists($area, 'image');
            } else {
                $this->settings->content = content($area, null);
            }

            if ($this->settings->content) {
                $this->settings->src = $this->settings->content ?? null;
                if ($this->settings->src) {
                    $this->settings->alt = $this->settings->src->description ?? null;
                    $this->settings->title = $this->settings->src->title ?? null;
                }
            }
        }
    }

    public function id()
    {
        if ($this->settings->inline) {
            return $this->settings->content->id ?? null;
        }
    }

    public function inline () {
        return $this->settings->inline;
    }

    public function size()
    {
        $size = $this->settings->size;

        if (is_string($size)) {
            $size = strtolower($size);

            if (strpos($size, 'x') > 0) {
                list($width, $height) = explode('x', $size);
                return "{$width}x{$height}";
            }

            if (is_numeric($size)) {
                return "{$size}x{$size}";
            }

            return $size;
        }

        if (is_int($size)) {
            return "{$size}x{$size}";
        }

        if (is_array($size)) {
            list($width, $height) = $size;
            return "{$width}x{$height}";
        }

        return (!$size && current_mode() === 'edit' && !$this->settings->content) ? '256x256' : null;
    }

    public function mode()
    {
        if (!$this->size()) {
            return Picture::MODE_ORIGINAL;
        }

        $mode = $this->settings->mode ?? null;
        $mode = (!$mode && ($this->settings->size ?? null)) ? Picture::MODE_EXACT : $mode;
        return $mode ?? Picture::MODE_ORIGINAL;
    }

    public function color()
    {
        $color = $this->settings->color ?? null;
        return $color;
    }

    public function path () {
        if (is_object($this->settings->src) && property_exists($this->settings->src, 'path')) {
            return $this->settings->src->path;
        }

        if (is_object($this->settings->src) && property_exists($this->settings->src, 'image')) {
            return $this->settings->src->image;
        }

        if (is_array($this->settings->src) && array_key_exists('path', $this->settings->src)) {
            return $this->settings->src['path'];
        }

        if (is_array($this->settings->src) && array_key_exists('image', $this->settings->src)) {
            return $this->settings->src['image'];
        }

        if (is_string($this->settings->src)) {
            return $this->settings->src;
        }
    }

    public function src()
    {
        $src = $this->path();
        $src = $src ?
            media_url(
                $src,
                $this->size(),
                $this->mode(),
                $this->color()
            ) : $src;

        if (!$src && current_mode() === 'edit') {
            return "https://placehold.it/{$this->size()}";
        }

        return $src;
    }

    public function srcSets()
    {
        return picture_srcsets(
            array_merge(['path' => $this->src()], (array) $this->settings)
        );
    }

    public function alt()
    {
        return $this->settings->alt;
    }

    public function title()
    {
        return $this->settings->title;
    }

    public function imageClass()
    {
        return $this->settings->imageClass ?? null;
    }

    public function class()
    {
        return $this->settings->class ?? null;
    }

    public function style()
    {
        return $this->settings->style ?? null;
    }

    public function imageStyle()
    {
        return $this->settings->imageStyle ?? null;
    }

    public function shouldRender()
    {
        return current_mode() === 'live' && !$this->path() ? false : true;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('nf::picture');
    }
}
