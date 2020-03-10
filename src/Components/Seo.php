<?php

namespace Netflex\Pages\Components;

use Illuminate\View\Component;
use Artesaos\SEOTools\Facades\SEOTools;

class Seo extends Component
{
    public $title;
    public $description;
    public $images = [];
    public $canonical;
    public $suffix = false;

    public function __construct($title = null, $description = null, $images = [], $canonical = null, $suffix = false)
    {
        $this->title = $title;
        $this->description = $description;
        $this->images = $images;
        $this->canonical = $canonical;
        $this->suffix = $suffix;
    }

    public function tags () {
        $seo = SEOTools::setTitle($this->title ?? SEOTools::getTitle(), $this->suffix);

        if ($page = current_page()) {
            $seo->setTitle($page->title, $this->suffix)
                ->setDescription($page->description);
        }

        if ($this->title) {
            $seo->setTitle($this->title, $this->suffix);
        }

        if ($this->description) {
            $seo->setDescription($this->description);
        }

        if (count($this->images)) {
            $seo->addImages($this->images);
        }

        if ($this->canonical) {
            $seo->setCanonical($this->canonical);
        }

        return $seo->generate();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('nf::seo');
    }
}
