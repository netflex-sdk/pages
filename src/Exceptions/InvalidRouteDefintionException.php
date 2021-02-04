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

    /** @var int */
    protected $error;

    /** @var string */
    protected $key;

    const E_UNKNOWN = 0;
    const E_UNDEFINED = 1;
    const E_ACTION = 2;
    const E_URL = 3;
    const E_METHODS = 4;
    const E_INVALID = 5;
    const E_ILLEGAL_KEY = 6;

    public function __construct($class, $routeDefinition = null, $error = 0, $key = null)
    {
        $this->class = $class;
        $this->routeDefinition = $routeDefinition;
        $this->key = $key;

        $this->error = 0;

        if (in_array($error, [static::E_UNKNOWN, static::E_UNDEFINED, static::E_ACTION, static::E_URL, static::E_METHODS])) {
            $this->error = $error;
        }

        parent::__construct(sprintf('Invalid route definition(s) in %s', $this->class));
    }

    public function getSolution(): Solution
    {
        $solution = BaseSolution::create('One or more of the routes defined in "' . $this->class . '" is invalid.');

        switch ($this->error) {
            case static::E_UNDEFINED:
                $solution->setSolutionDescription('No definition provided');
                break;
            case static::E_ACTION:
                $solution->setSolutionDescription('No route action provided');
                break;
            case static::E_URL:
                $solution->setSolutionDescription('No route url provided');
                break;
            case static::E_METHODS:
                $solution->setSolutionDescription('No route method(s) provided');
                break;
            case static::E_INVALID:
                $solution->setSolutionDescription('The $routes array is invalid');
                break;
            case static::E_ILLEGAL_KEY:
                $solution->setSolutionDescription('Invalid key: "' . $this->key . '"');
                break;
            default:
                $solution->setSolutionDescription('The route is invalid');
                break;
        }

        return $solution->setDocumentationLinks([
            'Netflex SDK documentation' => 'https://netflex-sdk.github.io/#/docs/routing?id=creating-the-custom-controller',
        ]);
    }
}
