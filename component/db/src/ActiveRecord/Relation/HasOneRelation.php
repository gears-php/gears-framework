<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord\Relation;

use Gears\Db\ActiveRecord\ActiveRecord;

/**
 * HasOne active record relation via the foreign key
 * @package Gears\Db\ActiveRecord
 */
class HasOneRelation extends RelationAbstract implements SingleRecordRelation
{
    /**
     * {@inheritdoc}
     */
    public function buildQuery()
    {
        $this->query = $this->manager->createQuery($this->metadata['class']);
        $tableName = $this->ownerMetadata['tableName'];
        $tableAlias = uniqid($tableName[0]);
        $this->query
            ->join(
                [$tableAlias => $tableName],
                $this->metadata['foreign'],
                $this->query->getTableName(),
                $pk = $this->ownerMetadata['primaryKey']
            )
            ->getWhere()->eq([$tableAlias => $pk]);
    }

    /**
     * {@inheritdoc}
     */
    public function exec(mixed $ownerId): ?ActiveRecord
    {
        return $this->query->bind(0, $ownerId)->fetchOne();
    }
}
