<?php

namespace Netflex\Pages\Components;

use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Netflex\Foundation\GlobalContent;

class GlobalValue extends Component
{
    public $area;
    public $block;
    public $column;
    public $content;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($area, $block = null, $column = null)
    {
        $this->area = $area ? $area : null;
        $this->block = $block ? $block : null;
        $this->column = $column ? $area : null;
        $this->value = static_content($area, $block, $column);
    }

    public function shouldRender()
    {
        return $this->value;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('nf::static-content');
    }
}
