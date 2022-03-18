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
        return once(function () use ($preset) {
            if ($path = $this->getPathAttribute()) {
                if ($preset) {
                    return media_url($this->getPathAttribute(), $preset);
                }

                return cdn_url($path);
            }
        });
    }
}
