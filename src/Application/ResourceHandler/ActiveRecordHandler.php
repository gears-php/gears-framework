<?php

declare(strict_types=1);

namespace Gears\Framework\Application\ResourceHandler;

use Gears\Framework\Application\ServiceAware;
use Gears\Db\ActiveRecord\ActiveRecord;

/**
 * Handler for Gears ActiveRecord resources
 */
class ActiveRecordHandler implements ResourceHandlerInterface
{
    use ServiceAware;

    public function list(string $resource): array
    {
        return $this->getActiveRecord()->query($resource)->fetchRecords();
    }

    public function one(string $resource, string $id): ActiveRecord
    {
        $query = $this->getActiveRecord()->query($resource);

        if (!$record = $query->fetchById($id)) {
            throw new ResourceNotFoundException($resource . "[$id]");
        }

        return $record;
    }

    public function post(string $resource): ActiveRecord
    {
        $payload = json_decode($this->get('request')->getContent(), true);
        $record = $this->getActiveRecord()->create($resource);
        unset($payload[$record->getPrimaryKey()]);
        $record->fill($payload);
        $record->save();

        return $record;
    }

    public function put(string $resource, string $id): ActiveRecord
    {
        $payload = json_decode($this->get('request')->getContent(), true);

        if (!$record = $this->getActiveRecord()->query($resource)->fetchById($id)) {
            throw new ResourceNotFoundException($resource . "[$id]");
        }

        $record->fill($payload);
        $record->save();

        return $record;
    }
}