<?php

namespace Netflex\Pages\Exceptions;

use Exception;
use Netflex\Pages\Contracts\CompilesException;

class NotImplementedException extends Exception implements CompilesException
{
  protected $class;
  protected $method;

  public function __construct($class, $method)
  {
    $this->class = $class;
    $this->method = $method;

    parent::__construct('Method ' . $method . '() not implemented in ' . $class);
  }

  public function compile()
  {
    return static::class . '("' . $this->class . '", "' . $this->method . '")';
  }
}
