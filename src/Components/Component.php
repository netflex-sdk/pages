<?php

namespace Netflex\Pages\Components;

use Illuminate\View\Component as BaseComponent;

/**
 * @deprecated 3.0.0 Replaced by \Illuminate\View\DynamicComponent
 */
class Component extends BaseComponent
{
    public $variables;
    public $component;

    public function __construct($is, $attributes = [])
    {
        trigger_deprecation('netflex/pages', '3.0.0', 'Netflex\Pages\Components\Component is deprecated, please replace with Illuminate\View\DynamicComponent');
        $this->component = $is;
        $this->variables = $attributes;
    }

    public function render()
    {
        return view('nf::component');
    }
}
