<?php

namespace Netflex\Pages\Exceptions;

use Exception;

class BreakpointsMissingException extends Exception
{
  public function __construct()
  {
    parent::__construct('No breakpoints defined. At least one required.');
  }
}
