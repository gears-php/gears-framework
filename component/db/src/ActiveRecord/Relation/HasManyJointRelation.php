<?php

namespace Gears\Db\ActiveRecord\Relation;

use Gears\Db\ActiveRecord\ActiveNode;
use Gears\Db\ActiveRecord\ActiveRecord;

/**
 * HasMany active record relation via the joint table
 * @package Gears\Db\ActiveRecord\Relation
 */
class HasManyJointRelation extends RelationAbstract
{
    /**
     * {@inheritdoc}
     */
    public function build(array $meta)
    {
        $this->query = $this->owner->getManager()->of($meta['class']);
        $target = $this->query->getActiveRecord();
        $this->query
            ->join(
                $jointTable = $meta['jointTable'],
                $meta['joinBy'],
                $target->getTableName(),
                $target->getPrimaryKey()
            )
            ->getWhere()->eq([$jointTable => $meta['matchBy']]);
    }

    /**
     * {@inheritdoc}
     * @return ActiveRecord[]
     */
    public function exec(): array
    {
        $this->query->bind(0, $this->owner->{$this->owner->getPrimaryKey()});

        return $this->query->getActiveRecord() instanceof ActiveNode
            ? $this->query->fetchTree()
            : $this->query->fetchAll();
    }
}
