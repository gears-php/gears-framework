<?php

namespace Gears\Framework\Application\Controller;

use Gears\Framework\Application\HttpExceptionInterface;
use Gears\Framework\Application\Routing\Route;

class UndefinedActionException extends \RuntimeException implements HttpExceptionInterface
{
    public function __construct(ControllerResolver $controllerResolver, Route $route)
    {
        $message = sprintf(
            'Undefined action method "%s" in %s. Route name is "%s"',
            $controllerResolver->getAction(),
            $controllerResolver->getController(),
            $route->getName()
        );
        parent::__construct($message, 500);
    }
}
