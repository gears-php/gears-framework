<?php

declare(strict_types=1);

namespace Gears\Framework\Application\ResourceHandler;

use Gears\Framework\Application\ServiceAware;
use Gears\Db\Table\TableAbstract;
use Symfony\Component\HttpFoundation\JsonResponse;

class DbTableHandler implements ResourceHandlerInterface
{
    use ServiceAware;

    public function list(string $resource): JsonResponse
    {
        /** @var TableAbstract $table */
        $table = $this->get($resource);

        return new JsonResponse($table->fetchAll());
    }

    public function one(string $resource, string $id): JsonResponse
    {
        /** @var TableAbstract $table */
        $table = $this->get($resource);

        if (!$row = $table->fetchRow($id)) {
            throw new ResourceNotFoundException($resource . "[$id]");
        }

        return new JsonResponse($row);
    }
}