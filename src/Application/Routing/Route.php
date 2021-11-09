<?php

declare(strict_types=1);

namespace Gears\Framework\Application\Routing;

/**
 * Object representation of a single routing rule
 * @package Gears\Framework\Application\Routing
 * @author denis.krasilnikov@gears.com
 */
class Route
{
    public const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

    /**
     * Route unique name
     */
    private string $name;

    /**
     * Route params parsed from the matched request uri
     */
    private array $params = [];

    /**
     * Additional attributes related to this route
     */
    private array $attributes = [];

    /**
     * Route pattern for request uri matching
     */
    private string $matchPattern;

    /**
     * The definition of the handler which should be dispatched for current route
     */
    private string $handlerDefinition;

    /**
     * List of http methods route is restricted to
     */
    private array $httpMethods = [];

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
     */
    public function getAllowedMethods(): array
    {
        return $this->httpMethods;
    }

    /**
     * Set routing url parameter
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Get all route url parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function setAttribute(string $name, mixed $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    public function getAttribute(string $name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Set name of resource this route is targeted to.
     */
    public function setResource(string $resource)
    {
        $this->setAttribute('@resource', $resource);

        return $this;
    }

    /**
     * Get name of resource this route is targeted to.
     */
    public function getResource(): ?string
    {
        return $this->getAttribute('@resource');
    }

    /**
     * Process match pattern in order to get additional info like HTTP methods limitation
     *
     * @param string $matchPattern
     * @return string Final processed matching patter
     */
    protected function processMatchPattern(string $matchPattern)
    {
        // matching allowed http methods (if any)
        $methodsRegex = sprintf('/(\b(%s)\b)+/', implode('|', self::METHODS));
        preg_match_all($methodsRegex, $matchPattern, $methods);

        if (count($methods[0])) {
            // limit allowed methods
            $this->httpMethods = array_intersect(self::METHODS, $methods[0]);
            $matchPattern = trim(str_replace(implode(' ', $methods[0]), '', $matchPattern));
        }

        return $matchPattern;
    }
}
