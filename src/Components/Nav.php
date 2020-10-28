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
  public $activeClass;
  public $dropdownClass;
  public $liClass;
  public $aClass;

  /**
   * Create a new component instance.
   *
   * @return void
   */
  public function __construct($parent = null, $levels = null, $type = 'nav', $root = null, $activeClass = null, $dropdownClass = 'dropdown-container', $liClass = null, $aClass = null)
  {
    $this->levels = $levels;
    $this->type = $type;
    $this->root = $root;
    $this->activeClass = $activeClass;
    $this->dropdownClass = $dropdownClass;
    $this->liClass = $liClass;
    $this->aClass = $aClass;
    $this->children = navigation_data($parent, $type, $root);
  }

  /**
   * @param object $child
   * @return bool
   */
  public function isActive($child)
  {
    if ($page = current_page()) {
      return $child->id === $page->id;
    }

    return false;
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
