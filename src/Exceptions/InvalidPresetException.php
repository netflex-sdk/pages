<?php

namespace Netflex\Pages\Exceptions;

use Exception;

class InvalidPresetException extends Exception
{
  public function __construct($preset)
  {
    parent::__construct('Invalid preset: ' . $preset);
  }
}
