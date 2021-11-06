<?php

declare(strict_types=1);

namespace Gears\Framework\Application\ResourceHandler;

use Symfony\Component\HttpFoundation\Response;

interface ResourceHandlerInterface
{
    public function list(string $resource): mixed;
    public function one(string $resource, string $id): mixed;
}