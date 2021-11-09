<?php

declare(strict_types=1);

namespace Gears\Framework\Application\ResourceHandler;

use Gears\Db\ActiveRecord\ActiveQuery;
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
        /** @var ActiveQuery $query */
        $query = $this->get('arm')->of($resource);

        return $this->get('request')->query->has('__ar:tree')
            ? $query->fetchTree()
            : $query->fetchAll();
    }

    public function one(string $resource, string $id): ActiveRecord
    {
        /** @var ActiveQuery $query */
        $query = $this->get('arm')->of($resource);

        if (!$record = $query->fetchById($id)) {
            throw new ResourceNotFoundException($resource . "[$id]");
        }

        return $record;
    }

    public function post(string $resource): ActiveRecord
    {
        $payload = json_decode($this->get('request')->getContent(), true);
        /** @var ActiveRecord $record */
        $record = $this->get('arm')->create($resource);
        $record->fill($payload);
        $record->save();

        return $record;
    }

    public function put(string $resource, string $id): ActiveRecord
    {
        $payload = json_decode($this->get('request')->getContent(), true);

        /** @var ActiveRecord $record */
        if (!$record = $this->get('arm')->of($resource)->fetchById($id)) {
            throw new ResourceNotFoundException($resource . "[$id]");
        }

        $record->fill($payload);
        $record->save();

        return $record;
    }
}