<?php

namespace Netflex\Pages\Components;

use Netflex\Pages\Page;
use Illuminate\View\Component;

class Nav extends Component
{
  public $levels;
  public $children;
  public $type;
  public $root;

  /**
   * Create a new component instance.
   *
   * @return void
   */
  public function __construct($parent = null, $levels = null, $type = 'nav', $root = null)
  {
    $this->levels = $levels;
    $this->type = $type;
    $this->root = $root;
    $this->children = navigation_data($parent, $type, $root);
  }

  /**
   * Get the view / contents that represent the component.
   *
   * @return \Illuminate\View\View|string
   */
  public function render()
  {
    return view('nf::nav');
  }
}
