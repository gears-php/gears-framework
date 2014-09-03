<?php
/**
 * @package   Gears\Db
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Db\Table;

use Gears\Db\Table\Relation\Through as ThroughRelation;
use Gears\Db\Table\Relation\ForeignKey as ForeignKeyRelation;
use Gears\Db\Table\Relation\RelationAbstract;
use Gears\Db\Adapter\AdapterAbstract;
use Gears\Db\Query;
use Gears\Db\Query\WhereAnd;

/**
 * Advanced implementation of Table Data Gateway pattern
 * @package    Gears\Db
 * @subpackage Database
 */
abstract class TableAbstract
{
    /**
     * Table name
     * @var string
     */
    protected $tableName;

    /**
     * Table field set
     * @var array
     */
    protected $tableFields = ['*'];

    /**
     * Table primary key
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Default ORDER BY clause field set
     * @var array
     */
    protected $orderBy = [null];

    /**
     * Stores objects of all table relations to other tables
     * @see TableRelation
     * @var array
     */
    protected $relations = [];

    /**
     * Db adapter instance holder
     * @var AdapterAbstract
     */
    private $db;

    /**
     * Table query instance
     * @var Query
     */
    private $query;

    /**
     * Constructor
     */
    public function __construct(AdapterAbstract $db)
    {
        if (is_null($this->tableName)) {
            throw new \Exception(get_called_class() . ' - table name is not specified');
        }

        $this->db = $db;
        $this->resetQuery();
        $this->init();
    }

    /**
     * Fetch rows using current query configuration
     * @return array Result row set
     */
    public function fetchRows()
    {
        return $this->getQuery()->exec()->fetchAll();
    }

    /**
     * Fetch and return limited rows number. Uses current query configuration
     * @param integer $pageNumber
     * @param integer $rowsPerPage
     * @return array
     */
    public function fetchPage($pageNumber = 1, $rowsPerPage = 10)
    {
        $this->getQuery()->limit(--$pageNumber * $rowsPerPage, $rowsPerPage);
        $this->getQuery()->calcFoundRows();
        return $this->fetchRows();
    }

    /**
     * Fetch a single row data by a given row id. Returns a simple property-value fields object.
     * For each specific table field, a field alias (if defined in table metadata) is used as an object
     * property name under which to store the field value. Db field name itself used otherwise.
     * By default replaces table foreign key fields with actual row data from relative tables accessed by relation name
     * @param int|array $idOrWhere Row id or Where clause array to find row by
     * @param array|string|bool $relations (optional) List by which to limit loaded relations. <i>false</i> to not load them at all
     * @return object Simple property-value object
     */
    public function fetchRow($idOrWhere, $relations = true)
    {
        // primary key value given
        if ($idOrWhere && is_scalar($idOrWhere)) {
            $idOrWhere = [$this->primaryKey => intval($idOrWhere)];
        }

        // yank db only if where present
        if (is_array($idOrWhere)) {
            // fetch all own table row data
            $q = $this->createQuery()->select($this->tableFields, null, $this->getTableName());
            $q->getWhere()->fromArray($idOrWhere);
            $row = $q->exec()->fetchRow();
        }

        // no row data fetched, fill all fields with NULL value
        if (empty($row)) {
            foreach ($this->tableFields as $fieldKey => $fieldValue) {
                $row[is_string($fieldKey) ? $fieldKey : $fieldValue] = null;
            }
        }

        // load relation table rows
        if ($relations && count($this->relations)) {
            // explode coma separated relations string
            if (is_string($relations)) {
                $relations = explode(',', $relations);
            }

            // no limitation array, use all relations
            if (!is_array($relations)) {
                $relations = $this->relations;
            } else {
                // limit table relations to use by given ones
                $relations = array_intersect_key($this->relations, array_flip($relations));
            }

            // add data of relations
            foreach ($relations as $relationName => $relation) {
                $relation = $this->getRelationObject($relationName);
                $relation->addData($row);
            }
        }

        return (object)$row;
    }

    /**
     * Get empty (dummy) table row
     */
    public function newRow()
    {
        return $this->fetchRow(0);
    }

    /**
     * Insert or update db record depending on record primary key presence among the input data
     * @param array|object $row Record field values
     * @return integer Record id
     */
    public function save($row)
    {
        // casting input data to array in case of object was given
        if (is_object($row)) {
            $row = (array)$row;
        }

        // replace data `alias` keys with real db field names
        foreach ($this->tableFields as $fieldKey => $fieldValue) {
            if (is_string($fieldKey) && isset($row[$fieldKey])) {
                $row[$fieldValue] = $row[$fieldKey];
                unset($row[$fieldKey]);
            }
        }

        // filter out accidental (non table field one) keys
        $row = array_intersect_key($row, array_flip($this->tableFields));

        // do insert/update depending on primary key value presence
        if (!empty($row[$this->primaryKey])) {
            $this->update($row, $rowId = $row[$this->primaryKey]);
        } else {
            $rowId = $this->insert($row);
        }

        return $rowId;
    }

    /**
     * Insert new table record
     * @param array $row
     * @return integer New record id
     */
    public function insert($row)
    {
        $this->getDb()->insert($this->tableName, [$row]);

        return $this->getDb()->getLastInsertId();
    }

    /**
     * Update table record matched by id
     * @param array $data New data
     * @param integer $rowId Record id
     */
    public function update($data, $rowId)
    {
        $table = $this->getDb()->escapeIdentifier($this->getTableName());
        $this->getDb()->update($table, $data, [$this->primaryKey => $rowId]);
    }

    /**
     * Delete table record by a given id
     * @param integer $rowId
     */
    public function delete($rowId)
    {
        $table = $this->getDb()->escapeIdentifier($this->getTableName());
        $this->getDb()->delete($table, [$this->primaryKey => $rowId]);
    }

    /**
     * Add table own field to the selection query
     * @param string $field Table field name or alias
     * @param string (optional) $alias Alias name for selection
     * @return TableAbstract
     */
    public function select($field, $alias = null)
    {
        $this->getQuery()->select($this->getFieldName($field), $alias, $this->getTableName());
        return $this;
    }

    /**
     * Filter table data by given field value
     * @param string|array $field Field name/alias or [table => field] pair
     * @param mixed $value Value by which to filter
     * @param boolean $allowEmpty Whether to apply filter if empty/zero field value was passed
     * @return TableAbstract
     */
    public function filter($field, $value, $allowEmpty = true)
    {
        if ($allowEmpty || !empty($value)) {
            if (!is_array($field)) {
                if ($fieldName = $this->getFieldName($field)) {
                    $field = $fieldName; // replace input alias by real field name
                }
            }
            $this->getQuery()->getWhere()->eq($field, $value);
        }

        return $this;
    }

    /**
     * Join current table with the relative one. By default all fields from
     * the joined table are selected but this can be limited using second parameter
     * (passing <i>false</i> will exclude all of joined fields)
     * @param string $relationName Name of the relation to be applied
     * @param array|string|bool $relationFields (optional) List of fields to be selected from the relative table
     * @throws \Exception Basic exception in case relation is not found
     * @return TableAbstract
     */
    public function with($relationName, $relationFields = true)
    {
        if (isset($this->relations[$relationName])) {
            $relation = $this->getRelationObject($relationName);
            $relation->addTo($this);

            // we need to select some fields from the joined table
            if ($relationFields) {
                if (is_string($relationFields)) {
                    $relationFields = explode(',', $relationFields);
                }

                // add all default fields from joined table to current table selection query
                foreach ($relation->getTable()->getFields() as $fieldAlias => $fieldName) {
                    if (is_numeric($fieldAlias)) {
                        $fieldAlias = $fieldName;
                    }

                    // restrict fields we are selecting from joined table
                    if (!is_array($relationFields) || in_array($fieldAlias, $relationFields)) {
                        $this->getQuery()->select($fieldName, $relationName . '_' . $fieldAlias, $relationName);
                    }
                }
            }
        } else {
            throw new \Exception(sprintf(get_called_class() . '::with() - no "%s" relation has been found', $relationName));
        }

        return $this;
    }

    /**
     * Get db adapter used by the table
     * @return AdapterAbstract
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Get table name
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Get primary key name
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Get default field set
     * @return array
     */
    public function getFields()
    {
        return $this->tableFields;
    }

    /**
     * Get table inner query instance
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get table order by fields
     * @return array
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * Create new table query instance
     * @return Query
     */
    public function createQuery()
    {
        return (new Query($this->db))
            ->select($this->getFields(), null, $this->getTableName())
            ->from($this->getTableName())
            ->where(new WhereAnd($this->db))
            ->order($this->orderBy);
    }

    /**
     * Reset table inner query to default
     */
    public function resetQuery()
    {
        $this->query = $this->createQuery();
    }

    /**
     * Method to be extended by descendant table classes
     * in case they require some initial preparations
     */
    protected function init()
    {
    }

    /**
     * Return field name by the given field alias
     * @param string $alias
     * @return string|null
     */
    private function getFieldName($alias)
    {
        return isset($this->tableFields[$alias]) ? $this->tableFields[$alias] : $alias;
    }

    /**
     * Create and return relation object from metadata (if it is not created yet)
     * @param string $relationName Table relation name
     * @return RelationAbstract
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    private function getRelationObject($relationName)
    {
        if (isset($this->relations[$relationName])) {
            $rel = & $this->relations[$relationName];

            if (is_array($rel)) { // create relation for the first time
                if (isset($rel['foreign'])) {
                    $rel = new ForeignKeyRelation($this, $this->relations[$relationName]);
                } elseif (isset($rel['through'])) {
                    $rel = new ThroughRelation($this, $this->relations[$relationName]);
                } else {
                    $msg = 'The "%s" relation definition is missing one of supported relation types: %s';
                    throw new \LogicException(sprintf($msg, $relationName, implode(', ', ['foreign', 'through'])));
                }

                $rel->setName($relationName);
            }

            return $rel;
        } else {
            throw new \InvalidArgumentException(sprintf('Relation with the "%s" name was not founded', $relationName));
        }
    }
}
