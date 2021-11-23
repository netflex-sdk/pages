<?php

namespace Netflex\Pages\Exceptions;

use Exception;
use Netflex\Pages\Contracts\CompilesException;

class PageNotBoundException extends Exception implements CompilesException
{
  public function __construct()
  {
    parent::__construct('Page not bound in application container');
  }

  public function compile()
  {
    return static::class . '()';
  }
}
