<?php

namespace Gears\Framework\Application\Controller;

use Gears\Framework\Application\Routing\Route;

class ControllerResolver
{
    /**
     * Controller FQCN
     */
    private string $controller;

    /**
     * Action method name
     */
    private string $action;

    public function __construct(private readonly ?string $controllerNamespacePrefix)
    {
    }

    /**
     * Take the controller action definition from the route and resolve it into controller instance and action name
     */
    public function resolve(Route $route): self
    {
        $handler = $route->getHandlerDefinition();
        list($class, $this->action) = explode(':', $handler);

        if (!class_exists($class)) {
            $class = rtrim($this->controllerNamespacePrefix ?: 'App\Controller', '\\') . '\\' . $class . 'Controller';
        }

        $this->controller = $class;

        return $this;
    }

    /**
     * Get controller class name
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * Get action name
     */
    public function getAction(): string
    {
        return $this->action;
    }
}
