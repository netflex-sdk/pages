<?php

namespace Netflex\Pages;

use Netflex\Support\Accessors;
use Netflex\Pages\Contracts\MediaUrlResolvable;

/**
 * @property int|null $id
 * @property int|null $file
 * @property string|null $title
 * @property string|null $description
 * @package Netflex\Pages
 */
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

    public function getFileAttribute($file)
    {
        if ($file ?? $this->attributes['file'] ?? null) {
            return (int) $file;
        }
    }

    public function getPathAttribute()
    {
        return $this->attributes['path'] ?? null;
    }
}
