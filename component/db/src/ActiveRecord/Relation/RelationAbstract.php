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
    /**
     * Query instance used for relational records fetching
     */
    protected ActiveQuery $query;

    /**
     * Build concrete relation based on given metadata
     */
    abstract public function build(array $meta);

    /**
     * Execute query against given target record and return relational ones. Should
     * return a single relation record or relation record list depending on concrete
     * implementation
     *
     * @return ActiveRecord|ActiveRecord[]
     */
    abstract public function exec(): mixed;

    public function __construct(protected string $name, protected ActiveRecord $owner)
    {
    }
}
