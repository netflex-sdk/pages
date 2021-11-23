<?php

namespace Netflex\Pages\Exceptions;

use Exception;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;
use Facade\IgnitionContracts\BaseSolution;
use Netflex\Pages\Controllers\Controller;
use Netflex\Pages\Contracts\CompilesException;

class InvalidControllerException extends Exception implements ProvidesSolution, CompilesException
{
    public $controller;

    public function __construct($controller)
    {
        parent::__construct('Incompatible controller ' . $controller);
        $this->controller = $controller;
    }

    public function getSolution(): Solution
    {
        $solution = BaseSolution::create('The controller for this route is not compatible with ' . Controller::class);
        $solution->setSolutionDescription('Please ensure that the controller inherits from ' . Controller::class);

        return $solution->setDocumentationLinks([
            'Netflex SDK documentation' => 'https://netflex-sdk.github.io/#/docs/routing?id=creating-the-custom-controller',
        ]);
    }

    public function compile () {
        return static::class . '("' . $this->controller . '")';
    }
}