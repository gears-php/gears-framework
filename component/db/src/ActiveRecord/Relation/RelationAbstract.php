<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord\Relation;

use Gears\Db\ActiveRecord\ActiveManager;
use Gears\Db\ActiveRecord\ActiveQuery;
use Gears\Db\ActiveRecord\ActiveRecord;

/**
 * Base class of any concrete active record relation type
 * @package Gears\Db\ActiveRecord\Relation
 */
abstract class RelationAbstract
{
    protected ActiveManager $manager;

    /**
     * Relation name
     */
    protected string $name;

    /**
     * Query instance used for relational records fetching
     */
    protected ActiveQuery $query;

    /**
     * Build concrete relation based on given metadata
     */
    abstract public function build(array $meta);

    /**
     * Execute query against given owner record and return relational ones. Should
     * return a single relation record or relation record list depending on concrete
     * implementation
     *
     * @return ActiveRecord|ActiveRecord[]
     */
    abstract public function exec(ActiveRecord $owner);

    public function __construct(string $name, ActiveManager $manager)
    {
        $this->name = $name;
        $this->manager = $manager;
    }
}
