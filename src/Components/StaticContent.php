<?php

namespace Netflex\Pages\Components;

use Illuminate\View\Component;

class StaticContent extends Component
{
    public $content;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($block, $area = null, $column = null)
    {
        $this->content = static_content($block, $area, $column);
    }

    public function shouldRender()
    {
        return $this->content !== null && trim($this->content) !== '';
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
