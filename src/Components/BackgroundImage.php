<?php

namespace Netflex\Pages\Components;

use Netflex\Pages\Components\Picture as Component;

class BackgroundImage extends Component
{
  /** @var string */
  public $is;

  /**
   * Create a new component instance.
   *
   * @return void
   */
  public function __construct($area = null, $alt = null, $title = null, $src = null, $mode = null, $width = null, $height = null, $size = null, $fill = null, $imageClass = null, $pictureClass = null, $preset = 'default', $direction = null, $is = null)
  {
    parent::__construct($area, $alt, $title, $src, $mode, $width, $height, $size, $fill, $imageClass, $pictureClass, $preset, $direction);
    $this->is = $is;
  }

  public function srcSets()
  {
    $srcSets = parent::srcSets();

    usort($srcSets, function ($a, $b) {
      return $b['maxWidth'] - $a['maxWidth'];
    });

    return $srcSets;
  }

  public function shouldRender()
  {
    return current_mode() === 'edit' || $this->src() || $this->is;
  }

  /**
   * Get the view / contents that represent the component.
   *
   * @return \Illuminate\View\View|string
   */
  public function render()
  {
    return view('nf::background-image');
  }
}
