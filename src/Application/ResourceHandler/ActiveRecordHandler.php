<?php

declare(strict_types=1);

namespace Gears\Framework\Application\ResourceHandler;

use Gears\Db\ActiveRecord\ActiveQuery;
use Gears\Framework\Application\ServiceAware;
use Gears\Db\ActiveRecord\ActiveRecord;
use Symfony\Component\HttpFoundation\JsonResponse;

class ActiveRecordHandler implements ResourceHandlerInterface
{
    use ServiceAware;

    public function list(string $resource): mixed
    {
        /** @var ActiveQuery $query */
        $query = $this->get('arm')->of($resource);

        return $query->fetchAll();
    }

    public function one(string $resource, string $id): mixed
    {
        /** @var ActiveQuery $query */
        $query = $this->get('arm')->of($resource);

        if (!$record = $query->fetchById($id)) {
            throw new ResourceNotFoundException($resource . "[$id]");
        }

        return $record;
    }
}