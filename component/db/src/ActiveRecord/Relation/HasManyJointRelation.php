<?php

namespace Gears\Db\ActiveRecord\Relation;

use Gears\Db\ActiveRecord\ActiveRecord;

/**
 * HasMany active record relation via the joint table
 * @package Gears\Db\ActiveRecord\Relation
 */
class HasManyJointRelation extends RelationAbstract
{
    /**
     * Joint table name
     * @var string
     */
    protected $jointTable;

    /**
     * Joint table field for joining with relation owner
     * @var string
     */
    protected $joinByField;

    /**
     * Joint table field for joining with relation target
     * @var string
     */
    protected $inverseByField;

    /**
     * {@inheritdoc}
     * @param array $meta
     */
    public function build(array $meta)
    {
        $this->jointTable = $meta['joint']['table'];
        $this->joinByField = $meta['joint']['joinBy'];
        $this->inverseByField = $meta['joint']['inverseBy'];
        $this->query = $this->manager->of($meta['class']);
    }

    /**
     * {@inheritdoc}
     * @return ActiveRecord[]
     */
    public function exec(ActiveRecord $owner): array
    {
        $target = $this->query->getActiveRecord();
        $this->query
            ->join(
                $this->jointTable,
                $this->inverseByField,
                $target->getTableName(),
                $target->getPrimaryKey()
            )
            ->join(
                $owner->getTableName(),
                $owner->getPrimaryKey(),
                $this->jointTable,
                $this->joinByField
            );
        $this->query->getWhere()->eq([$owner->getTableName() => $owner->getPrimaryKey()]);
        $this->query->bind(0, $owner->{$owner->getPrimaryKey()});

        return $this->query->fetchAll();
    }
}
