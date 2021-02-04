<?php

namespace Netflex\Pages\Contracts;

/**
 * @property string $path
 * @package Netflex\Pages\Contracts
 */
interface MediaUrlResolvable
{
    /** @return string */
    public function getPathAttribute();
}
