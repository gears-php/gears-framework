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

    /**
     * Take the controller action definition from the route and resolve it into controller instance and action name
     */
    public function resolve(Route $route): self
    {
        $handler = $route->getHandlerDefinition();
        list($module, $class, $action) = explode(':', $handler);

        if (!class_exists($class)) {
            $class = implode('\\', [$module, 'Controller', $class . 'Controller']);
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
