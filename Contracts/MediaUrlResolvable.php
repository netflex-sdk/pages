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

    /**
     * @param string|null $preset 
     * @return string|null
     */
    public function url($preset);
}
