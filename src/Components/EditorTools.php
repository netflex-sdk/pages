<?php

namespace Netflex\Pages\Components;

use Illuminate\View\Component;

class EditorTools extends Component
{
    public function editorTools () {
        return editor_tools();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('nf::editor-tools');
    }
}
