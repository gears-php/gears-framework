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
     * @param Route $route
     * @return bool Whether the given route was successfully resolved
     */
    public function resolve(Route $route)
    {
        if ($handler = $route->getHandlerDefinition()) {
            list($module, $controller, $action) = explode(':', $handler);
            $class = implode('\\', [$module, 'Controller', $controller . 'Controller']);
            $this->controller = new $class;
            $this->action = $action . 'Action';

            return true;
        }

        return false;
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
