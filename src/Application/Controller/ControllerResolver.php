<?php

namespace Gears\Framework\Application\Controller;

use Gears\Framework\Application\Routing\Route;

class ControllerResolver
{
    /**
     * Controller instance
     * @var AbstractController
     */
    private $controller;

    /**
     * Action name
     * @var string
     */
    private $action;

    /**
     * Take the controller action definition from the route and resolve it into controller instance and action name
     */
    public function resolve(Route $route): self
    {
        $handler = $route->getHandlerDefinition();
        list($module, $class, $action) = explode(':', $handler);

        if (!class_exists($class)) {
            $class = implode('\\', [$module, 'Controller', $className . 'Controller']);
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
     * @return AbstractController
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Get action name
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
}
