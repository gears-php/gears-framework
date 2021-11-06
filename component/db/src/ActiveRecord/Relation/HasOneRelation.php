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
     * Foreign key of the relation owner
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
     * @return ActiveRecord
     */
    public function exec(ActiveRecord $owner): ActiveRecord
    {
        return $this->query->fetchById($owner->{$this->foreignKey});
    }
}
