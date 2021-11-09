<?php

namespace Gears\Framework\Application\Controller;

use Gears\Framework\Application\HttpExceptionInterface;

class ActionNotFoundException extends \RuntimeException implements HttpExceptionInterface
{
    public function __construct(ControllerResolver $controllerResolver)
    {
        $message = sprintf(
            'Method "%s" not found in %s',
            $controllerResolver->getAction(),
            get_class($controllerResolver->getController())
        );
        parent::__construct($message, 404);
    }
}
