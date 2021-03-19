<?php

namespace Netflex\Pages\Components;

use Netflex\Pages\Page;
use Illuminate\View\Component;

class Breadcrumbs extends Component
{
    public $items = [];

    protected $current;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($current = null, $root = true, $parent = null, $homeLabel = null, $prepend = [], $append = [])
    {
        if ($current instanceof Page) {
            $this->current = $current;
        }

        if (!$this->current) {
            if ($page = Page::find($current)) {
                $this->current = $page;
            }
        }

        if (!$this->current) {
            $this->current = current_page();
        }

        if ($this->current instanceof Page) {
            $page = $this->current;

            do {
                if ($page->type === Page::TYPE_DOMAIN) {
                    break;
                }

                if ($parent) {
                    if ($parent instanceof Page) {
                        if ($page->id == $parent->id) {
                            $this->items[] = $page;
                            break;
                        }
                    }
                    if ($parent == $page->id) {
                        $this->items[] = $page;
                        break;
                    }
                }

                $this->items[] = $page;
                $page = $page->parent;
            } while ($page);
        }

        $this->items = array_reverse($this->items);

        $this->items = array_map(function ($item) {
            return $item;
        }, $this->items);

        $this->items = array_filter($this->items);
        $this->items = array_values($this->items);

        if (count($this->items)) {
            if ($root) {
                $this->items[0]->name = $homeLabel ? __($homeLabel) : $this->items[0]->name;
            } else {
                array_shift($this->items);
                $this->items = array_values($this->items);
            }
        }

        $append = collect($append)->flatten()->toArray();

        if (count($append)) {
            foreach ($append as $item) {
                if ($item instanceof Page) {
                    array_unshift($this->items, $item);
                }

                if (is_array($item) && array_key_exists('name', $item) && array_key_exists('url', $item)) {
                    array_unshift($this->items, (object) $item);
                }

                if (is_object($item) && property_exists($item, 'name') && property_exists($item, 'url')) {
                    array_unshift($this->items, $item);
                }
            }
        }

        $prepend = collect($prepend)->flatten()->toArray();

        if (count($prepend)) {
            foreach ($prepend as $item) {
                if ($item instanceof Page) {
                    array_push($this->items, $item);
                }

                if (is_array($item) && array_key_exists('name', $item) && array_key_exists('url', $item)) {
                    array_push($this->items, (object) $item);
                }

                if (is_object($item) && property_exists($item, 'name') && property_exists($item, 'url')) {
                    array_push($this->items, $item);
                }
            }
        }

        $this->items = array_values($this->items);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('nf::breadcrumbs');
    }
}
