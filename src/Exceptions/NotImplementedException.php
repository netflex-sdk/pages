<?php

namespace Netflex\Pages\Exceptions;

use Exception;

class NotImplementedException extends Exception
{
  public function __construct($class, $method)
  {
    parent::__construct('Method ' . $method . '() not implemented in ' . $class);
  }
}
