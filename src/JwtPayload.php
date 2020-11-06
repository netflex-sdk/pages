<?php

namespace Netflex\Pages;

use Netflex\Support\Accessors;
use Illuminate\Support\Traits\Macroable;

/**
 * @property-read string $relation
 * @property-read string $scope
 * @property-read string|null $view
 * @property-read string|null $mode
 * @property-read int|null $page_id
 * @property-read int|null $revision_id
 * @property-read string|null $edit_tools
 * @property-read string|null $edit_tools
 * @property-read string $domain
 * @property-read int $uid
 * @property-read int $iat
 * @property-read int $exp
 * @property-read string $iss
 */
class JwtPayload
{
    use Accessors;
    use Macroable;

    /** @var array */
    protected $attributes = [];

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function getModeAttribute ($mode = null) {
        return $mode ?? 'live';
    }
}
