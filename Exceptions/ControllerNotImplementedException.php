<?php

namespace Netflex\Pages\Exceptions;

use Exception;
use Facade\IgnitionContracts\BaseSolution;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;

class ControllerNotImplementedException extends Exception implements ProvidesSolution
{
  protected string $className;
  protected string $namespace;
  protected string $class;

  public function __construct (string $namespace, string $className)
  {
    $this->className = $className;
    $this->namespace = $namespace;
    $this->class = "\\{$namespace}\\{$className}";

    parent::__construct('Controller ' . $this->class . ' not implemented');
  }

  public function getSolution (): Solution
  {
    return BaseSolution::create('Controller ' . $this->class . ' not implemented')
      ->setSolutionDescription(
        "Implement missing page controller\n\n`§ php artisan make:controller {$this->className}`"
      );
  }
}
