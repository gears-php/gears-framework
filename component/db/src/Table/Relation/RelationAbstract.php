<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 5/29/14
 * Time: 6:59 PM
 */

namespace Gears\Db\Table\Relation;

use Gears\Db\Table\TableAbstract;

abstract class RelationAbstract
{
    /**
     * Static tables storage per all RelationAbstract instances
     * @var array
     */
    protected static $tables = [];

    /**
     * Relation referencing (target) table
     * @var TableAbstract
     */
    protected $table;

    /**
     * Relation owner (master) table
     * @var TableAbstract
     */
    protected $ownerTable;

    /**
     * Relation name
     * @var string
     */
    protected $name;

    /**
     * Construct table relation using the given metadata
     * @param string $relationName
     * @param TableAbstract $owner Relation owning table
     * @param array $metadata
     */
    public function __construct($relationName, TableAbstract $owner, array $metadata)
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
     * @return TableAbstract
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Use the given table and add all necessary joins in order to fetch relational data
     * @param TableAbstract $table
     * @return void
     */
    abstract public function addTo(TableAbstract $table);

    /**
     * Add the relational row(s) data to the given data row
     * @param array $row
     */
    abstract public function addData(array &$row);
}
