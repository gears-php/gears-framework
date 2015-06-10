<?php
/**
 * @package   Gears\Db
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Db\Table;

use Gears\Db\Table\Relation\HasOneRelation;
use Gears\Db\Table\Relation\HasManyRelation;
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
    protected $tableFields = [];

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
     * @var RelationAbstract[]
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
     * Get all table relations
     * @return Relation\RelationAbstract[]
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Return table relation object representation. Internally
     * creates relation from metadata if not yet done
     * @param string $relationName Table relation name
     * @return RelationAbstract
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getRelation($relationName)
    {
        if (isset($this->relations[$relationName])) {
            $rel = &$this->relations[$relationName];

            if (is_array($rel)) { // create relation for the first time
                switch ($rel['type']) {
                    case 'hasOne':
                        $rel = new HasOneRelation($relationName, $this, $this->relations[$relationName]);
                        break;

                    case 'hasMany':
                        $rel = new HasManyRelation($relationName, $this, $this->relations[$relationName]);
                        break;

                    default:
                        throw new \RuntimeException(sprintf('The type of "%s" relation is unrecognized or not set', $relationName));
                }
            }

            return $rel;
        } else {
            throw new \InvalidArgumentException(sprintf('Relation with the "%s" name was not founded', $relationName));
        }
    }

    /**
     * Fetch all rows using current query configuration
     * @param Query $query
     * @return array
     */
    public function fetchAll(Query $query = null)
    {
        $query = $query ?: $this->getQuery();

        return $query->exec()->fetchAll();
    }

    /**
     * Fetch multiple rows grouped by the first selection column
     * @return array
     */
    public function fetchAssoc()
    {
        return $this->getQuery()->exec()->fetchAssoc();
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

        return $this->fetchAll();
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
                $relation = $this->getRelation($relationName);
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
     * @param array|string|bool $withFields (optional) List of fields to be selected from the relative table
     * @throws \Exception Basic exception in case relation is not found
     * @return TableAbstract
     */
    public function with($relationName, $withFields = true)
    {
        if (isset($this->relations[$relationName])) {
            $relation = $this->getRelation($relationName);
            $relation->addTo($this);

            // we need to select some fields from the joined table
            if ($withFields) {
                if (is_string($withFields)) {
                    $withFields = explode(',', $withFields);
                }

                if ($relationFields = $relation->getTable()->getFields()) {
                    // add all default fields from joined table to current table selection query
                    foreach ($relationFields as $fieldAlias => $fieldName) {
                        if (is_numeric($fieldAlias)) {
                            $fieldAlias = $fieldName;
                        }

                        // restrict fields we are selecting from joined table
                        if (!is_array($withFields) || in_array($fieldAlias, $withFields)) {
                            $this->getQuery()->select($fieldName, $relationName . '_' . $fieldAlias, $relationName);
                        }
                    }
                } elseif (is_array($withFields)) {
                    throw new \Exception(sprintf(get_called_class() . '::with() - The "%s" relation table has no fields defined', $relationName));
                } else {
                    // if no relation table fields set explicitly select all
                    $this->getQuery()->select('*', null, $relationName);
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
}
