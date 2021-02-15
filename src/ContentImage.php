<?php

namespace Netflex\Pages;

use Netflex\Support\Accessors;

class ContentImage extends ContentFile
{
    /**
     * @param string|null $preset 
     * @return string|null 
     */
    public function url($preset = 'default')
    {
        if ($path = $this->getPathAttribute()) {
            if ($preset) {
                return media_url($this->getPathAttribute(), $preset);
            }

            return cdn_url($path);
        }
    }
}
