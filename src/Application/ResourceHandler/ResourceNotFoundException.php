<?php

namespace Gears\Framework\Application\ResourceHandler;

use Gears\Framework\Application\Controller\ControllerResolver;

class ResourceNotFoundException extends \RuntimeException
{
    public function __construct(string $resource, $code = 404, \Exception $previous = null)
    {
        $message = sprintf("Resource $resource was not found");
        parent::__construct($message, $code, $previous);
    }
}
