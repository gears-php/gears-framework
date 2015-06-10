<?php

namespace Gears\Db\Table\Relation;

use Gears\Db\Query;
use Gears\Db\Table\TableAbstract;

/**
 * Object relation of some particular table to another one
 * via the joint table or via the `reversed` foreign key
 * @package    Gears\Db
 * @subpackage Database
 */
class HasManyRelation extends RelationAbstract
{
    /**
     * The joint table used for many-to-many case
     * @var TableAbstract
     */
    protected $jointTable;

    /**
     * The foreign key of the relation target table
     * @var string
     */
    protected $foreignKey;

    /**
     * {@inheritdoc}
     */
    public function __construct($relationName, TableAbstract $owner, array $metadata)
    {
        parent::__construct($relationName, $owner, $metadata);

        if (isset($metadata['joint'])) {
            $jointTableClassName = $metadata['joint'];

            if (!isset(self::$tables[$jointTableClassName])) {
                self::$tables[$jointTableClassName] = new $jointTableClassName($owner->getDb());
            }

            $this->jointTable = self::$tables[$jointTableClassName];
        } elseif (isset($metadata['foreign'])) {
            $this->foreignKey = $metadata['foreign'];
        } else {
            $error = 'A `hasMany` relation should define either `joint` property (in case of joint table relation) or `foreign` property (in case of foreign key relation). None is found for the "%s" relation.';
            throw new \RuntimeException(sprintf($error, $this->name));
        }
    }

    /**
     * With current relation type we are not able to use joins for
     * fetching relational data within a single query of owning table
     * {@inheritdoc}
     */
    public function addTo(TableAbstract $table)
    {
        throw new \RuntimeException('You can not perform this operation with hasMany relation');
    }

    /**
     * Build the query, fetch the data from the relation table and add them to the main row
     * {@inheritdoc}
     */
    public function addData(array &$row)
    {
        if ($this->jointTable) {
            // build query for fetching relation table data via the joint table
            $query = $this->getTable()
                ->createQuery()
                ->join(
                    $this->jointTable->getTableName(),
                    $this->getJointTableRelation($this->getTable())->getForeignKey(),
                    $this->getTable()->getTableName(),
                    $this->getTable()->getPrimaryKey()
                );

            $query->getWhere()->eq(
                $this->getJointTableRelation($this->ownerTable)->getForeignKey(),
                $row[$this->ownerTable->getPrimaryKey()]
            );

        } else {
            // build query for fetching relation table data via its foreign key
            $query = $this->getTable()->createQuery();
            $query->getWhere()->eq($this->foreignKey, $row[$this->ownerTable->getPrimaryKey()]);
        }

        $row[$this->name] = $this->getTable()->fetchAll($query);
    }

    /**
     * Find hasOne relation by its target class inside joint table
     * @param TableAbstract $targetTable
     * @return HasOneRelation
     */
    protected function getJointTableRelation($targetTable)
    {
        foreach ($this->jointTable->getRelations() as $relationName => &$relation) {
            // @todo remove this workaround code after refactoring table relation definitions from classes to config files
            if (!is_object($relation)) {
                $relation = $this->jointTable->getRelation($relationName);
            }

            if (get_class($relation->getTable()) == get_class($targetTable)) {
                return $relation;
            }
        }

        return null;
    }
}
