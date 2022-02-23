<?php

namespace Netflex\Pages\Components;

use Illuminate\View\Component;

class Inline extends Component
{
    public $class;
    public $style;
    public $tag;
    public $area;
    public $content;
    public $default;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($area, $class = null, $style = null, $is = null, $default = null)
    {
        $this->area = blockhash_append($area);
        $this->class = $class;
        $this->style = $style;
        $this->tag = $is;
        $this->default = $default;

        if (current_mode() === 'edit') {
            $this->content = insert_content_if_not_exists($this->area, 'html', $default);
        } else {
            $this->content = content($area, null);
        }
    }

    public function tag()
    {
        if (current_mode() === 'edit') {
            return $this->tag ?? 'div';
        }

        return $this->tag;
    }

    public function id()
    {
        return $this->content->id ?? null;
    }

    public function value()
    {
        if ($value = ($this->content ? ($this->content->html ?? null) : null)) {
            return $value;
        }

        return $this->default;
    }

    public function shouldRender()
    {
        return current_mode() === 'live' && !$this->value() ? false : true;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('netflex-pages::inline');
    }
}
