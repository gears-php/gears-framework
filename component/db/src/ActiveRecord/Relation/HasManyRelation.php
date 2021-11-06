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
     * Foreign key of the relation subject
     * @var string
     */
    protected $foreignKey;

    /**
     * {@inheritdoc}
     * @param array $meta
     */
    public function build(array $meta)
    {
        $this->foreignKey = $meta['foreign'];
        $this->query = $this->manager->of($meta['class']);
    }

    /**
     * {@inheritdoc}
     * @return ActiveRecord[]
     */
    public function exec(ActiveRecord $owner): array
    {
        $meta = $this->query->getActiveRecord()->getMetadata();
        $this->query->getWhere()->eq($meta['fields'][$this->foreignKey]);
        $this->query->bind(0, $owner->{$owner->getPrimaryKey()});

        return $this->query->fetchAll();
    }
}
