<?php

declare(strict_types=1);

namespace Gears\Framework\Application\ResourceHandler;

interface ResourceHandlerInterface
{
    public function list(string $resource): mixed;

    public function one(string $resource, string $id): mixed;

    public function post(string $resource): mixed;

    public function put(string $resource, string $id): mixed;
}