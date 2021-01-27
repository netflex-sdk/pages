<?php

namespace Netflex\Pages\Components;

use Netflex\Pages\Page;
use Illuminate\View\Component;
use Netflex\Pages\NavigationData;

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
  public function __construct($parent = null, $levels = null, $type = 'nav', $root = null, $activeClass = null, $dropdownClass = 'dropdown-container', $liClass = null, $aClass = null, $showTitle = false)
  {
    if ($parent instanceof Page) {
      $parent = $parent->id;
    }

    $this->levels = $levels;
    $this->type = $type;
    $this->root = $root;
    $this->activeClass = $activeClass;
    $this->dropdownClass = $dropdownClass;
    $this->liClass = $liClass;
    $this->aClass = $aClass;
    $this->children = NavigationData::get($parent, $type, $root);
    $this->showTitle = $showTitle;
  }

  /**
   * @param NavigationData $child
   * @return string
   */
  public function aClassList(NavigationData $child)
  {
    return implode(' ', array_filter([$this->aClass, $child->active ? $this->activeClass : null]));
  }

  /**
   * @return string
   */
  public function dropdownClassList()
  {
    return implode(' ', array_filter([$this->attributes->get('class'), $this->dropdownClass]));
  }

  /**
   * @return int|null
   */
  public function dropdownLevels()
  {
    return $this->levels !== null ? ($this->levels - 1) : $this->levels;
  }

  /**
   * @param object $child
   * @deprecated v3.1.5 This is no longer needed, as the active status can be checked on the NavigationData object directly
   * @return bool
   */
  public function isActive(NavigationData $child)
  {
    return $child->active;
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
