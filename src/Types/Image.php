<?php

namespace Netflex\Pages\Types;

use Netflex\Support\ReactiveObject;

class Image extends ReactiveObject
{
  public function __toString()
  {
    return $this->path ?? '';
  }
}
