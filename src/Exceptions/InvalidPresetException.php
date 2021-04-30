<?php

namespace Netflex\Pages\Exceptions;

use Exception;
use Netflex\Pages\Contracts\CompilesException;

class InvalidPresetException extends Exception implements CompilesException
{
  protected $preset;

  public function __construct($preset)
  {
    $this->preset = $preset;
    parent::__construct('Invalid preset: ' . $preset);
  }

  public function compile()
  {
    return static::class . '("' . $this->preset . '")';
  }
}
