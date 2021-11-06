<?php

namespace Gears\Framework\Application\Controller;

use Gears\Framework\Application\Controller\ControllerResolver;

class ActionNotFoundException extends \RuntimeException
{
    public function __construct(ControllerResolver $controllerResolver, $code = 0, \Exception $previous = null)
    {
        $message = sprintf(
            'Method "%s" not found in %s',
            $controllerResolver->getAction(),
            get_class($controllerResolver->getController())
        );
        parent::__construct($message, $code, $previous);
    }
}
