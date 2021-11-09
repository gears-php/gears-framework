<?php

namespace Gears\Db\ActiveRecord\Relation;

use Gears\Db\ActiveRecord\ActiveRecord;

/**
 * HasOne active record relation via the foreign key
 * @package Gears\Db\ActiveRecord\Relation
 */
class HasOneRelation extends RelationAbstract
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
                $meta['foreign'],
                $this->query->getActiveRecord()->getTableName(),
                $pk = $this->owner->getPrimaryKey()
            )
            ->getWhere()->eq([$tableName => $pk]);
    }

    /**
     * {@inheritdoc}
     */
    public function exec(): ActiveRecord
    {
        return $this->query
            ->bind(0, $this->owner->{$this->owner->getPrimaryKey()})
            ->fetchOne();
    }
}
