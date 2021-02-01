<?php

namespace Netflex\Pages\Exceptions;

use Exception;
use Facade\IgnitionContracts\ProvidesSolution;
use Facade\IgnitionContracts\Solution;
use Facade\IgnitionContracts\BaseSolution;

class InvalidRouteDefintionException extends Exception implements ProvidesSolution
{
    /** @var string */
    protected $class;

    /** @var object|null */
    protected $routeDefinition;

    public function __construct($class, $routeDefinition = null)
    {
        $this->class = $class;
        $this->routeDefinition = $routeDefinition;

        parent::__construct('Invalid Route Definition(s) in Controller');
    }

    public function getSolution(): Solution
    {
        $hasDefintion = (bool) $this->routeDefinition;
        $hasAction = (bool) (isset($this->routeDefinition->action) && !empty($this->routeDefinition->action));
        $hasUrl = (bool) (isset($this->routeDefinition->url) && !empty($this->routeDefinition->url));
        $hasMethods = (bool) (
            (isset($this->routeDefinition->methods) && is_array($this->routeDefinition->methods) && !empty($this->routeDefinition->methods)) ||
            (isset($this->routeDefinition->method) && is_string($this->routeDefinition->method) && !empty($this->routeDefinition->method)));

        $solution = BaseSolution::create('One or more of the routes defined in "' . $this->class . '" is invalid.');

        if (!$hasDefintion) {
            $solution = $solution->setSolutionDescription('No definition provided');
        }

        if (!$hasAction) {
            $solution = $solution->setSolutionDescription('No route action provided');
        }

        if (!$hasUrl) {
            $solution = $solution->setSolutionDescription('No route url provided');
        }

        if (!$hasMethods) {
            $solution = $solution->setSolutionDescription('No route method(s) provided');
        }

        return $solution->setDocumentationLinks([
            'Netflex SDK documentation' => 'https://netflex-sdk.github.io/#/docs/routing?id=creating-the-custom-controller',
        ]);
    }
}
