<?php

namespace Netflex\Pages\Components;

use Illuminate\View\Component;

class Blocks extends Component
{
  public $area;
  public $variables;
  public $blocks = [];

  /**
   * Create a new component instance.
   *
   * @return void
   */
  public function __construct($area, $variables = [])
  {
    $this->area = blockhash_append($area);
    $this->variables = $variables;
    $this->blocks = blocks($this->area);
  }

  public function shouldRender()
  {
    return $this->blocks->count() > 0;
  }

  /**
   * Get the view / contents that represent the component.
   *
   * @return \Illuminate\View\View|string
   */
  public function render()
  {
    return view('netflex-pages::blocks');
  }
}
