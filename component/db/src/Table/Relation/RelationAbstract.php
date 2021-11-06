<?php

declare(strict_types=1);

namespace Gears\Db\Table\Relation;

use Gears\Db\Table\TableAbstract;

abstract class RelationAbstract
{
    /**
     * Static tables storage per all RelationAbstract instances
     */
    protected static array $tables = [];

    /**
     * Relation referencing (target) table
     */
    protected TableAbstract $table;

    /**
     * Relation owner (master) table
     */
    protected TableAbstract $ownerTable;

    /**
     * Relation name
     */
    protected string $name;

    /**
     * Construct table relation using the given metadata
     * @param string $relationName
     * @param TableAbstract $owner Relation owning table
     * @param array $metadata
     */
    public function __construct(string $relationName, TableAbstract $owner, array $metadata)
    {
        $this->name = $relationName;

        if (!isset($metadata['class'])) {
            throw new \RuntimeException(sprintf('Relation `class` property not found for "%s"', $this->name));
        }

        $tableClassName = $metadata['class'];

        if (!isset(self::$tables[$tableClassName])) {
            self::$tables[$tableClassName] = new $tableClassName($owner->getDb());
        }

        $this->table = self::$tables[$tableClassName];
        $this->ownerTable = $owner;
    }

    /**
     * Get the relation target table
     */
    public function getTable(): TableAbstract
    {
        return $this->table;
    }

    /**
     * Use the given table and add all necessary joins in order to fetch relational data
     */
    abstract public function addTo(TableAbstract $table): void;

    /**
     * Add the relational row(s) data to the given data row
     */
    abstract public function addData(array &$row);
}
