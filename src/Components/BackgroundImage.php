<?php

namespace Netflex\Pages\Components;

use Netflex\Pages\Components\Picture as Component;

class BackgroundImage extends Component
{
    public function srcSets()
    {
        $srcSets = parent::srcSets();

        usort($srcSets, function ($a, $b) {
            return $b['maxWidth'] - $a['maxWidth'];
        });

        return $srcSets;
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
