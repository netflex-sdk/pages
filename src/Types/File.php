<?php

namespace Netflex\Pages\Types;

use Netflex\Support\ReactiveObject;
use Illuminate\View\Compilers\BladeCompiler;

class File extends ReactiveObject
{
  public function __toString()
  {
    return $this->path ?? $this->image ?? $this->file ?? $this->text ?? '';
  }
}
