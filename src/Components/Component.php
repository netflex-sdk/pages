<?php

namespace Netflex\Pages\Components;

use Illuminate\View\Component as BaseComponent;

class Component extends BaseComponent
{
    public $variables;
    public $component;

    public function __construct($is, $attributes = [])
    {
        $this->component = $is;
        $this->variables = $attributes;
    }

    public function render()
    {
        return view('nf::component');
    }
}
