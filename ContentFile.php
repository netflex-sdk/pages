<?php

namespace Netflex\Pages;

use ArrayAccess;
use JsonSerializable;

use Netflex\Support\Accessors;
use Netflex\Pages\Contracts\MediaUrlResolvable;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @property int|null $id
 * @property int|null $file
 * @property string|null $title
 * @property string|null $description
 * @package Netflex\Pages
 */
class ContentFile implements MediaUrlResolvable, JsonSerializable, Arrayable, Jsonable
{
    use Accessors;

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes ?? [];
    }

    /**
     * @param array|null $attributes 
     * @return static|null 
     */
    public static function cast($attributes = [])
    {
        if ($attributes && is_array($attributes) && array_key_exists('path', $attributes) && $attributes['path']) {
            return new static($attributes);
        }

        return null;
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

    /**
     * @param null $preset Not used
     * @return string|null 
     */
    public function url ($preset = null)
    {
        if ($path = $this->getPathAttribute()) {
            return cdn_url($path);
        }
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        return $this->attributes;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function __debugInfo()
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            $attributes[$key] = $this->__get($key);
        }

        return $attributes;
    }
}
