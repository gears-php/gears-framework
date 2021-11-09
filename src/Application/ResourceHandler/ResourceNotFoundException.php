<?php

declare(strict_types=1);

namespace Gears\Framework\Application\ResourceHandler;

use Gears\Framework\Application\HttpExceptionInterface;

class ResourceNotFoundException extends \RuntimeException implements HttpExceptionInterface
{
    public function __construct(string $resource)
    {
        parent::__construct("Resource $resource was not found", 404);
    }
}
