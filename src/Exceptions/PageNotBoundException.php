<?php

namespace Netflex\Pages\Exceptions;

use Exception;

class PageNotBoundException extends Exception
{
  public function __construct()
  {
    parent::__construct('Page not bound in application container');
  }
}
