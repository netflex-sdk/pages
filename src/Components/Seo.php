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

    public function __construct($title = null, $description = null, $images = [], $canonical = null, $suffix = true)
    {
        $this->title = $title;
        $this->description = $description;
        $this->images = $images;
        $this->canonical = $canonical;
        $this->suffix = $suffix;
    }

    public function tags () {

        if ($page = current_page()) {
            
            if(SEOTools::getTitle() === SEOTools::metatags()->getDefaultTitle()) {
                SEOTools::setTitle($page->title, $this->suffix);
            }
            
            if(!SEOTools::metatags()->getDescription()) {
                SEOTools::setDescription($page->description);
            }
                
        }

        if ($this->title) {
            SEOTools::setTitle($this->title, $this->suffix);
        }

        if ($this->description) {
            SEOTools::setDescription($this->description);
        }

        if (count($this->images)) {
            SEOTools::addImages($this->images);
        }

        if ($this->canonical) {
            SEOTools::setCanonical($this->canonical);
        }

        return SEOTools::generate();
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
