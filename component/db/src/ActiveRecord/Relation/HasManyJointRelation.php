<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord\Relation;

use Gears\Db\ActiveRecord\ActiveNode;

/**
 * HasMany active record relation via the joint table
 * @package Gears\Db\ActiveRecord
 */
class HasManyJointRelation extends RelationAbstract
{
    /**
     * {@inheritdoc}
     */
    public function buildQuery()
    {
        $this->query = $this->manager->createQuery($this->metadata['class']);
        $jointTable = $this->metadata['jointTable'];
        $jointAlias = uniqid($jointTable[0]);
        $this->query
            ->join(
                [$jointAlias => $jointTable],
                $this->metadata['joinBy'],
                $this->query->getTableName(),
                $this->query->getPrimaryKey(),
            )
            ->getWhere()->eq([$jointAlias => $this->metadata['matchBy']]);
    }

    /**
     * {@inheritdoc}
     */
    public function exec(mixed $ownerId): array
    {
        $this->query->bind(0, $ownerId);

        return $this->query->fetchRecords();
    }
}
