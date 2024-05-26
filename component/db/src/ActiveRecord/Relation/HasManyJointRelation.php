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
        $this->query = $this->manager->query($this->metadata['class']);
        $target = $this->query->getMetadata();
        $jointTable = $this->metadata['jointTable'];
        $jointAlias = uniqid($jointTable[0]);
        $this->query
            ->join(
                [$jointAlias => $jointTable],
                $this->metadata['joinBy'],
                $target['tableName'],
                $target['primaryKey'],
            )
            ->getWhere()->eq([$jointAlias => $this->metadata['matchBy']]);
    }

    /**
     * {@inheritdoc}
     */
    public function exec(mixed $ownerId): array
    {
        $this->query->bind(0, $ownerId);

        return $this->query->getMetadata() instanceof ActiveNode
            ? $this->query->fetchTree()
            : $this->query->fetchAll();
    }
}
