<?php

namespace Netflex\Pages\Components;

use Illuminate\View\Component;
use Netflex\Pages\Page;
use Apility\SEOTools\Facades\SEOMeta;

class Breadcrumbs extends Component
{
    public static $items = [];

    /** @var Page|null */
    public $root;

    /** @var Page|null */
    public $target;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($root = null, $target = null)
    {
        if (!($root instanceof Page)) {
            $root = Page::find($root);
        }

        $this->root = $root;

        if (!($target instanceof Page)) {
            $target = Page::find($target);
        }

        $this->target = $target;

        if (!current_page() && !count(static::$items)) {
            static::push();
        }
    }

    public function root()
    {
        if ($this->root) {
            return $this->root;
        }

        return Page::root();
    }

    public function target()
    {
        if ($this->target) {
            return $this->target;
        }

        if ($target = current_page()) {
            return $target;
        }

        return $this->root();
    }

    public function trail()
    {
        return once(function () {
            $items = [];

            if ($target = $this->target()) {
                $root = $this->root() ? $this->root()->id : null;
                $items = [
                    ...$target->trail($root)
                ];
            }

            if (count(static::$items)) {
                $items = [
                    ...$items,
                    ...static::$items
                ];
            }

            return $items;
        });
    }

    public static function push($title = null, $url = null)
    {
        if (!$url) {
            $url = request()->url();
        }

        if (!$title) {
            $metaTitle = null;
            if ($metaTitle = SEOMeta::getTitle()) {
                if (strpos($metaTitle, SEOMeta::getTitleSeparator() . SEOMeta::getDefaultTitle()) !== false) {
                    $metaTitle = str_replace(SEOMeta::getTitleSeparator() . SEOMeta::getDefaultTitle(), '', $metaTitle);
                }

                if ($metaTitle === SEOMeta::getDefaultTitle()) {
                    $metaTitle = null;
                }
            }

            if ($metaTitle) {
                $title = $metaTitle;
            }

            if (!$title) {
                $parts = explode('/', $url);
                $last = array_pop($parts);
                $title = ucwords(implode(' ', explode('-', $last)));
            }
        }

        static::$items[] = (object) [
            'name' => $title,
            'url' => $url
        ];
    }

    public function shouldRender()
    {
        return count($this->trail()) > 1;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('netflex-pages::bs5-breadcrumbs');
    }
}
