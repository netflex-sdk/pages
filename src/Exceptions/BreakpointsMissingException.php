<?php

namespace Netflex\Pages\Exceptions;

use Exception;
use Netflex\Pages\Contracts\CompilesException;

class BreakpointsMissingException extends Exception implements CompilesException
{
  public function __construct()
  {
    parent::__construct('No breakpoints defined. At least one required.');
  }

  public function compile()
  {
    return static::class . '()';
  }
}
