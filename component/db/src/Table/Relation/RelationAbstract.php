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
     * Relation table
     * @var TableAbstract
     */
    protected $table = null;

    /**
     * Relation owner table
     * @var TableAbstract
     */
    protected $owner = null;

    /**
     * Name of the current relation
     * @var string
     */
    protected $name = '';

    /**
     * Construct table relation using the given metadata
     * @param TableAbstract $owner Relation owning table
     * @param array $metadata
     */
    public function __construct(TableAbstract $owner, array $metadata)
    {
        $tableClassName = $metadata['class'];

        // build full relation table class name using namespace of the relation owner table class
        if (false === strpos($tableClassName, '\\')) {
            $tableClassName = preg_replace('/(\w+)$/', ucfirst($tableClassName), get_class($owner));
        }

        if (!isset(self::$tables[$tableClassName])) {
            self::$tables[$tableClassName] = new $tableClassName($owner->getDb());
        }

        $this->owner = $owner;
        $this->table = self::$tables[$tableClassName];
    }

    /**
     * Set relation name
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the relation table
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
