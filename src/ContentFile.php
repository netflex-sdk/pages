<?php

namespace Netflex\Pages;

use Netflex\Support\Accessors;
use Netflex\Pages\Contracts\MediaUrlResolvable;

class ContentFile implements MediaUrlResolvable
{
    use Accessors;

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function getIdAttribute($id)
    {
        if ($id ?? $this->attributes['id'] ?? null) {
            return (int) $id;
        }
    }

    public function getPathAttribute()
    {
        return $this->attributes['path'] ?? null;
    }
}
