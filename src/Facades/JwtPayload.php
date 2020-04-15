<?php

namespace Netflex\Pages\Facades;

use Illuminate\Support\Facades\Facade;

class JwtPayload extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'JwtPayload';
    }
}
