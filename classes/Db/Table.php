<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\Db;

use Gears\Framework\Db\Db;

/**
 * Advanced implementation of Table Data Gateway pattern
 * @package    Gears\Framework
 * @subpackage Database
 */
abstract class Table
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
     * @var Query
     */
    protected $defaultOrderBy = [null];

    /**
     * Stores objects of all table relations to other tables
     * @see TableRelation
     * @var array
     */
    protected $relations = [];

    /**
     * Db adapter instance holder
     * @var Adapter\Generic
     */
    private $db;

    /**
     * Table default query instance
     * @var Query
     */
    private $defaultQuery;

    /**
     * Table current (configurable) query instance
     * @var Query
     */
    private $query;

    /**
     * Constructor
     */
    public function __construct()
    {
        // init table db adapter
        $this->db = Db::getAdapter();

        if (is_null($this->tableName)) {
            throw new \Exception(get_called_class() . ' - table name is not specified');
        }

        // build default query
        $this->defaultQuery = new Query($this->db);
        $this->defaultQuery
            ->select($this->getDefaultFields(), null, $this->getTableName())
            ->from($this->getTableName())
            ->where(new QueryConditionAnd)
            ->orderBy($this->defaultOrderBy);

        // do custom preparations
        $this->init();

        // reset current table query selection
        $this->resetSelection();
    }

    /**
     * Add table own field to the selection query
     * @param string $fieldName Table field name or alias
     * @param string (optional) $fieldAlias
     */
    public function select($fieldName, $fieldAlias = null)
    {
        if (null === $fieldAlias && isset($this->tableFields[$fieldName])) {
            $fieldName = $this->tableFields[$fieldAlias = $fieldName];
        }
        $this->getQuery()->select($fieldName, $fieldAlias, $this->getTableName());
    }

    /**
     * Fetch rows using current query configuration
     * @return array Result row set
     */
    public function fetchRows()
    {
        return $this->getQuery()->execute();
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
     * @param integer $rowId
     * @param array|string|bool $relations (optional) List by which to limit loaded relations. <i>false</i> to not load them at all
     * @return object Simple property-value object
     */
    public function fetchRow($rowId, $relations = true)
    {
        // yank db only if positive row id number given
        if (intval($rowId)) {
            // fetch own table row data
            $rowData = $this->getDb()->query('SELECT ?# FROM ?# WHERE ?# = ?',
                array_values($this->tableFields),
                $this->tableName,
                $this->primaryKey,
                $rowId
            )->fetchRow();
        }

        // no row data fetched, fill all fields with NULL value
        if (empty($rowData)) {
            $rowData = array_fill_keys($this->tableFields, null);
            // prevent processing relations
            $relations = false;
        }

        // modify row data to use field aliases as array keys
        foreach ($this->tableFields as $fieldKey => $fieldName) {
            // we have a field alias as key
            if (is_string($fieldKey)) {
                $rowData[$fieldKey] = $rowData[$fieldName];
                unset($rowData[$fieldName]);
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

            foreach ($relations as $relationName => $relation) {
                // make sure we have a relation object
                $relation = $this->getRelation($relationName);
                // add relative table row to current table row
                $foreignName = $relation->getForeignName();
                // add relation table row data
                $rowData[$relationName] = $relation->getTable()->fetchRow($rowData[$foreignName]);
                // remove original table foreign field
                unset($rowData[$foreignName]);
            }
        }

        return (object)$rowData;
    }

    /**
     * Get empty (dummy) table row
     */
    public function newRow()
    {
        return $this->fetchRow(0);
    }

    /**
     * Inserts or updates db record depending on
     * record primary key presence among input data
     * @param array|object $rowData Record field values
     * @return integer Record id
     */
    public function saveRow($rowData)
    {
        // casting input data to array in case of object was given
        if (is_object($rowData)) {
            $rowData = (array)$rowData;
        }

        // modify row data to use db field names as array keys
        foreach ($this->tableFields as $fieldKey => $fieldName) {
            // we have field value under field alias key
            if (isset($rowData[$fieldKey])) {
                $rowData[$fieldName] = $rowData[$fieldKey];
                unset($rowData[$fieldKey]);
            }
        }

        // filter out accidental (non table field one) keys
        $rowData = array_intersect_key($rowData, array_flip($this->tableFields));

        // do insert/update depending on primary key value presence
        if (!empty($rowData[$this->primaryKey])) {
            $this->updateRow($rowData);
        } else {
            $rowData[$this->primaryKey] = $this->insertRow($rowData);
        }

        return $rowData[$this->primaryKey];
    }

    /**
     * Delete db row by a given id
     */
    public function deleteRow($rowId)
    {
        $this->getDb()->query('DELETE FROM ?# WHERE ?# = ?', $this->tableName, $this->primaryKey, $rowId);
    }

    /**
     * Filter table data by given field value
     * @param string|array $field Table field name
     * @param mixed $value Value by which to filter
     * @param boolean $allowEmpty Whether to apply filter if empty/zero field value was passed
     */
    public function filterBy($field, $value, $allowEmpty = true)
    {
        // field alias given, replace it with real db field name
        if (isset($this->tableFields[$field])) {
            $field = $this->tableFields[$field];
        }

        if ($allowEmpty || !empty($value)) {
            $this->getQuery()->getWhere()->add($field, $this->getDb()->escape($value));
        }
    }

    /**
     * Join current table with the relative one. By default all fields from
     * the joined table are selected but this can be limited using second parameter
     * (passing <i>false</i> will exclude all of joined fields)
     * @param string $relationName Name of the relation to be applied
     * @param array|string|bool $relationFields (optional) List of fields to be selected from the relative table
     * @throws \Exception Basic exception in case relation is not found
     */
    public function with($relationName, $relationFields = true)
    {
        if (isset($this->relations[$relationName])) {
            /** @var $relation TableRelation */
            $relation = $this->getRelation($relationName);

            // add join condition to link current table with the relation one
            $this->getQuery()->join(
            // alias => name of joined table
                [$relationName => $relation->getTable()->getTableName()],
                // relation table field used for linking
                $relation->getFieldName(),
                // current table field used for linking
                [$this->getTableName() => $relation->getForeignName()]
            );

            // we need to select some fields from the joined table
            if ($relationFields) {
                if (is_string($relationFields)) {
                    $relationFields = explode(',', $relationFields);
                }

                // add all default fields from joined table to current table selection query
                foreach ($relation->getTable()->getDefaultFields() as $fieldAlias => $fieldName) {
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
    }

    /**
     * Reset current table query configuration to the default
     */
    public function resetSelection()
    {
        unset($this->query);
        $this->query = clone $this->getDefaultQuery();
    }

    /**
     * @return Adapter\Generic
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Get current selection query instance
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get table name
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Get default field set to be used for default query
     */
    public function getDefaultFields()
    {
        return $this->tableFields;
    }

    /**
     * Get default query instance
     */
    protected function getDefaultQuery()
    {
        return $this->defaultQuery;
    }

    /**
     * Method to be extended by descendant table classes
     * in case they require some initial preparations
     */
    protected function init()
    {
    }

    /**
     * Create and return relation object from metadata (if it is not created yet)
     * @param string $relation Table relation name
     * @return TableRelation
     */
    private function getRelation($relation)
    {
        // relation metadata
        if (!is_object($this->relations[$relation])) {
            $this->relations[$relation] = new TableRelation($this, $this->relations[$relation]);
        }

        return $this->relations[$relation];
    }

    /**
     * Insert new database record data
     *
     * @param array $rowData
     * @return integer New record id
     */
    private function insertRow($rowData)
    {
        $this->getDb()->query('INSERT INTO ?# SET ?a', $this->tableName, $rowData);
        return $this->getDb()->getLastInsertId();
    }

    /**
     * Update database record with the given data
     *
     * @param array $rowData
     */
    private function updateRow($rowData)
    {
        $query = 'UPDATE ?# SET ?a WHERE ?# = ?';
        $this->getDb()->query($query, $this->tableName, $rowData, $this->primaryKey, $rowData[$this->primaryKey]);
    }
}