<?php

declare(strict_types=1);

namespace Gears\Framework\Application\ResourceHandler;

use Gears\Db\Dataset;
use Symfony\Component\HttpFoundation\JsonResponse;

use function Gears\Framework\Application\Helper\Db;

class DbTableHandler implements ResourceHandlerInterface
{
    public function list(string $resource): JsonResponse
    {
        $dataset = new Dataset($resource, Db());

        return new JsonResponse($dataset->fetchAll());
    }

    public function one(string $resource, string $id): JsonResponse
    {
        $dataset = new Dataset($resource, Db());

        if (!$row = $dataset->filter('id', $id)->fetchRow()) {
            throw new ResourceNotFoundException($resource . "[$id]");
        }

        return new JsonResponse($row);
    }

    public function post(string $resource): mixed
    {
        // TODO: Implement post() method.

    }

    public function put(string $resource, string $id): mixed
    {
        // TODO: Implement put() method.
    }
}