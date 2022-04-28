<?php

namespace Netflex\Pages\Components;

use Illuminate\View\Component;

use Netflex\Pages\NavigationData;
use Netflex\Pages\Page;

class Bs5Nav extends Component
{
    public $levels;
    public $children;
    public $type;
    public $root;

    /**
     * Create a new component instance.
     *
     * @param Page|int $parent
     * @param int|null $levels
     * @param string $type
     * @param string|null $root
     * @param string|null $activeClass
     * @param string|null $dropdownClass
     * @param string|null $liClass
     * @param string|null $aClass
     * @param bool $showTitle
     */
    public function __construct($parent = null, $levels = null, $type = 'nav', $root = null)
    {
        if ($parent instanceof Page) {
            $parent = $parent->id;
        }

        $this->levels = $levels;
        $this->type = $type;
        $this->root = $root;
        $this->children = NavigationData::get($parent, $type, $root);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('netflex-pages::bs5-nav');
    }
}
