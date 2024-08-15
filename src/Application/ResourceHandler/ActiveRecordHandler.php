<?php

declare(strict_types=1);

namespace Gears\Framework\Application\ResourceHandler;

use Gears\Db\ActiveRecord\ActiveRecord;

use function Gears\Framework\Application\Helper\{Model, Query, Request};

/**
 * Handler for Gears ActiveRecord resources
 */
class ActiveRecordHandler implements ResourceHandlerInterface
{
    public function list(string $resource): array
    {
        return Query($resource)->fetchRecords();
    }

    public function one(string $resource, string $id): ActiveRecord
    {
        if (!$record = Query($resource)->fetchById($id)) {
            throw new ResourceNotFoundException($resource . "[$id]");
        }

        return $record;
    }

    public function post(string $resource): ActiveRecord
    {
        $payload = json_decode(Request()->getContent(), true);
        $record = Model($resource);
        unset($payload[$record->getPrimaryKey()]);
        $record->fill($payload);
        $record->save();

        return $record;
    }

    public function put(string $resource, string $id): ActiveRecord
    {
        $payload = json_decode(Request()->getContent(), true);

        if (!$record = Query($resource)->fetchById($id)) {
            throw new ResourceNotFoundException($resource . "[$id]");
        }

        $record->fill($payload);
        $record->save();

        return $record;
    }
}