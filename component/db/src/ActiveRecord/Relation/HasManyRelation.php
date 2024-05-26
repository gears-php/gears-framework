<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord\Relation;

/**
 * HasMany active record relation via the inverse foreign key
 * @package Gears\Db\ActiveRecord
 */
class HasManyRelation extends RelationAbstract
{
    /**
     * {@inheritdoc}
     */
    public function buildQuery()
    {
        $this->query = $this->manager->query($this->metadata['class']);
        $tableName = $this->ownerMetadata['tableName'];
        $tableAlias = uniqid($tableName[0]);
        $this->query
            ->join(
                [$tableAlias => $tableName],
                $pk = $this->ownerMetadata['primaryKey'],
                $this->query->getMetadata()['tableName'],
                $this->metadata['foreign']
            )
            ->getWhere()->eq([$tableAlias => $pk]);
    }

    /**
     * {@inheritdoc}
     */
    public function exec(mixed $ownerId): array
    {
        return $this->query->bind(0, $ownerId)->fetchAll();
    }
}
