<?php

namespace Gears\Db\ActiveRecord\Relation;

use Gears\Db\ActiveRecord\ActiveRecord;

/**
 * HasMany active record relation via the inverse foreign key
 * @package Gears\Db\ActiveRecord\Relation
 */
class HasManyRelation extends RelationAbstract
{
    /**
     * {@inheritdoc}
     */
    public function build(array $meta)
    {
        $this->query = $this->owner->getManager()->of($meta['class']);
        $this->query
            ->join(
                $tableName = $this->owner->getTableName(),
                $pk = $this->owner->getPrimaryKey(),
                $this->query->getActiveRecord()->getTableName(),
                $meta['foreign']
            )
            ->getWhere()->eq([$tableName => $pk]);
    }

    /**
     * {@inheritdoc}
     * @return ActiveRecord[]
     */
    public function exec(): array
    {
        return $this->query
            ->bind(0, $this->owner->{$this->owner->getPrimaryKey()})
            ->fetchAll();
    }
}
