<?php

namespace Gears\Framework\Application\Routing;

/**
 * Object representation of a single routing rule
 * @package Gears\Framework\Application\Routing
 * @author deniskrasilnikov86@gmail.com
 */
class Route
{
    /**
     * Route unique name
     * @var string
     */
    protected $name;

    /**
     * Route params parsed from the matched request uri
     * @var array
     */
    protected $params = [];

    /**
     * Route pattern for request uri matching
     * @var string
     */
    protected $matchPattern;

    /**
     * The definition of the handler which should dispatched for current route
     * @var string
     */
    protected $handlerDefinition;

    /**
     * List of http methods route is restricted to
     * @var array
     */
    protected $httpMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

    /**
     * Initialize routing rule
     * @param string $name
     * @param string $matchPattern
     * @param string $handlerDefinition
     */
    public function __construct($name, $matchPattern, $handlerDefinition)
    {
        $this->name = $name;
        $this->matchPattern = $this->processMatchPattern($matchPattern);
        $this->handlerDefinition = $handlerDefinition;
    }

    /**
     * Ger route name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get route matching pattern
     * @return string
     */
    public function getMatchPattern()
    {
        return $this->matchPattern;
    }

    /**
     * Get route handler definition
     * @return string
     */
    public function getHandlerDefinition()
    {
        return $this->handlerDefinition;
    }

    /**
     * Get list of allowed http methods
     * @return array
     */
    public function getHttpMethods()
    {
        return $this->httpMethods;
    }

    /**
     * Add routing parameter
     * @param string $name
     * @param mixed $value
     */
    public function addParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Process match pattern in order to get additional info like HTTP method limitation
     * @param string $matchPattern
     * @return string Final processed matching patter
     */
    protected function processMatchPattern($matchPattern)
    {
        // matching allowed http methods (if any)
        $methodsRegex = sprintf('/(\b(%s)\b)+/', implode('|', $this->httpMethods));
        preg_match_all($methodsRegex, $matchPattern, $methods);

        if (count($methods[0])) {
            // limit allowed methods
            $this->httpMethods = array_intersect($this->httpMethods, $methods[0]);
            $matchPattern = trim(str_replace(implode(' ', $methods[0]), '', $matchPattern));
        }

        return $matchPattern;
    }
}
