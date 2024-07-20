<?php

namespace Gears\Framework\Application\Controller;

use Gears\Framework\Application\ResourceHandler\ResourceHandlerInterface;
use Gears\Framework\Application\Routing\Route;

class ControllerResolver
{
    /**
     * Controller instance
     */
    private AbstractController|ResourceHandlerInterface $controller;

    /**
     * Action name
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
        list($class, $action) = explode(':', $handler);

        if (!class_exists($class)) {
            $class = rtrim($this->controllerNamespacePrefix ?: 'App\Controller', '\\') . '\\' . $class . 'Controller';
        }

        $this->controller = new $class;
        $this->action = $action;

        if (!method_exists($this->controller, $this->action)) {
            throw new ActionNotFoundException($this);
        }

        return $this;
    }

    /**
     * Get controller instance
     */
    public function getController(): AbstractController|ResourceHandlerInterface
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
