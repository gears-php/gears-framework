<?php

namespace Gears\Framework\Application\Controller;

use Gears\Framework\Application\Application;
use Gears\Framework\Application\Routing\Route;

class ControllerResolver
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Take the controller action definition from the route and resolve it into callable
     * @param Route $route
     * @return array|bool
     */
    public function getController(Route $route)
    {
        if ($handler = $route->getHandlerDefinition()) {
            list($module, $controller, $action) = explode(':', $handler);
            $className = implode('\\', [$module, 'Controller', $controller . 'Controller']);
            return [new $className($this->app), "{$action}Action"];
        }

        return false;
    }
}
