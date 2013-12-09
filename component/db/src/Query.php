<?php
/**
 * @package   Gears\Db
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Db;

use Gears\Db\Adapter\AdapterAbstract;
use Gears\Db\Query\WhereAbstract;

/**
 * Query constructor class
 * @package Gears\Db
 */
class Query
{
    # sort direction constants
    const ASC = 'asc';
    const DESC = 'desc';

    /**
     * @var AdapterAbstract
     */
    private $db = null;

    /**
     * @var string
     */
    private $selectOptions = '';

    /**
     * @var array
     */
    private $select = [];

    /**
     * @var array
     */
    private $from = [];

    /**
     * @var array
     */
    private $join = [];

    /**
     * @var WhereAbstract
     */
    private $where = null;

    /**
     * @var array
     */
    private $group = [];

    /**
     * @var array
     */
    private $order = [];

    /**
     * @var null
     */
    private $limit = null;

    /**
     * Init query with the db adapter instance
     * @param AdapterAbstract $db
     */
    public function __construct(AdapterAbstract $db)
    {
        $this->db = $db;
    }

    /**
     * Add a single field or array of fields to the SELECT clause
     * <code>
     * $query->select('field', 'fieldAlias');
     * $query->select(['field1Alias' => 'field1', 'field2', 'field3']);
     * </code>
     * @param string|array $field Field name or array of fields
     * @param string $alias (optional) Field alias
     * @param string $table (optional) Table name
     * @return Query
     */
    public function select($field, $alias = null, $table = '')
    {
        if (is_array($field)) {
            // array of column fields was passed
            foreach ($field as $fieldKey => $fieldValue) {
                $this->selectSingle($fieldValue, is_string($fieldKey) ? $fieldKey : null, $table);
            }
        } else {
            $this->selectSingle($field, $alias, $table);
        }
        return $this;
    }

    /**
     * Select a single field wrapped in COUNT() aggregate function
     * {@see selectSingle()}
     * @param string $field
     * @param string $alias
     * @param string $table
     */
    public function selectCount($field, $alias = null, $table = null)
    {
        $this->selectSingle(['count' => $field], $alias, $table);
        return $this;
    }

    /**
     * Add a single field to SELECT clause
     * @param string|array $field Field name or [aggregate_function => field] mapping
     * @param string $alias (optional) Field alias
     * @param string $table (optional) Table name
     * @return Query
     */
    public function selectSingle($field, $alias = null, $table = null)
    {
        $fn = '%s';
        if (is_array($field)) { // function => field
            $key = key($field);
            if (!is_numeric($key)) {
                $fn = strtoupper($key) . '(%s)';
            }
            $field = current($field);
        }

        $field = $this->db->escapeIdentifier($field);
        if (!empty($table)) {
            $field = $this->db->escapeIdentifier($table) . '.' . $field;
        }

        $field = sprintf($fn, $field);

        if (!empty($alias)) {
            $field .= ' AS ' . $this->db->escapeIdentifier($alias);
        }

        $this->select[$field] = $field;
        return $this;
    }

    /**
     * Select all fields
     * return Query
     */
    public function selectAll()
    {
        $this->select[] = '*';
        return $this;
    }

    /**
     * Add FROM table
     * @param $table
     * @param null $alias
     * @return Query
     */
    public function from($table, $alias = null)
    {
        $table = $this->db->escapeIdentifier($table);
        if (is_string($alias) && '' != $alias) {
            $table .= ' AS ' . $this->db->escapeIdentifier($alias);
        }
        $this->from[] = $table;
        return $this;
    }

    /**
     * Add JOIN clause. Note that $joinTable alias (if given) will be used to qualify $joinField.
     * Table name is used otherwise. By default INNER JOIN is applied
     * @param string|array $joinTable Joined table name or [alias => name]
     * @param string $joinField Joined table field
     * @param string $baseTable Basic table to join with
     * @param string $baseField Basic table field to join on
     * @param string (optional) $type Join type
     * @return Query
     */
    public function join($joinTable, $joinField, $baseTable, $baseField, $type = 'inner')
    {
        if (is_array($joinTable)) {
            $joinAlias = key($joinTable);
            $joinTable = current($joinTable);
        } else {
            $joinAlias = $joinTable;
        }

        $this->join[] = sprintf('%s JOIN %s AS %s ON %s.%s = %s.%s',
            strtoupper($type),
            $this->db->escapeIdentifier($joinTable),
            $this->db->escapeIdentifier($joinAlias),
            $this->db->escapeIdentifier($joinAlias),
            $this->db->escapeIdentifier($joinField),
            $this->db->escapeIdentifier($baseTable),
            $this->db->escapeIdentifier($baseField)
        );
        return $this;
    }

    /**
     * Add LEFT JOIN clause. Note that $joinTable alias (if given) will be used to qualify $joinField.
     * Table name is used otherwise
     * @param string|array $joinTable Joined table name or [alias => name]
     * @param string $joinField Joined table field
     * @param string $baseTable Basic table to join with
     * @param string $baseField Basic table field to join on
     * @return Query
     */
    public function leftJoin($joinTable, $joinField, $baseTable, $baseField)
    {
        $this->join($joinTable, $joinField, $baseTable, $baseField, 'left');
        return $this;
    }

    /**
     * Add a {@see WhereAbstract} object of WHERE clause
     * @param WhereAbstract $where
     * @return Query
     */
    public function where(WhereAbstract $where)
    {
        $this->where = $where;
        return $this;
    }

    /**
     * Get WHERE clause conditions object
     * @return WhereAbstract
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Add GROUP BY clause field(s)
     * @param string|array $field
     * @param string (optional) $table
     * @return $this
     */
    public function group($field, $table = null)
    {
        if (is_array($field)) {
            foreach ($field as $fieldName) {
                $this->group($fieldName, $table);
            }
        } else {
            $field = $this->db->escapeIdentifier($field);
            if (!empty($table)) {
                $field = $this->db->escapeIdentifier($table) . '.' . $field;
            }
            $this->group[] = $field;
        }
        return $this;
    }

    /**
     * Add a single field or array of fields to the ORDER BY clause. Example:
     * <code>
     * $query->order('lastName', Query::SORT_ASC);
     * $query->order(['lastName', 'firstName' => Query::SORT_DESC]);
     * </code>
     * @param string|array $field Field name or array of fields ot order
     * @param string $sort (optional) Sort direction
     * @return Query
     */
    public function order($field, $sort = '')
    {
        if (is_array($field)) { // array of order-by fields
            foreach ($field as $fieldKey => $fieldValue) {
                // numeric element index
                if (is_numeric($fieldKey)) {
                    // just pass field name
                    $this->order($fieldValue);
                } // we have [field name => sort direction] pair
                else {
                    $this->order($fieldKey, $fieldValue);
                }
            }
        } else { // add a single column field
            if (null === $field) {
                $this->order[] = 'NULL';
            } else {
                $this->order[] = rtrim($this->db->escapeIdentifier($field) . ' ' . strtoupper($sort));
            }
        }
        return $this;
    }

    /**
     * Add selection limit clause
     * @param $offset
     * @param $rowCount
     * @return Query
     */
    public function limit($offset, $rowCount)
    {
        // @todo take the limit clause from specific db adapter implementation
        $this->limit = sprintf('LIMIT %d,%d', $offset, $rowCount);
        return $this;
    }

    /**
     * Add SQL_CALC_FOUND_ROWS option. Mysql specific
     */
    public function calcFoundRows()
    {
        $this->selectOptions .= ' SQL_CALC_FOUND_ROWS ';
        return $this;
    }

    /**
     * Execute query and return db adapter
     * @return AdapterAbstract
     */
    public function exec()
    {
        if (!count($this->select)) {
            $this->selectAll();
        }
        return $this->db->query($this);
    }

    /**
     * Get query SQL string
     * @return string
     */
    public function toString()
    {
        return $this->glue();
    }

    /**
     * Wrapper for the {@see toString()}
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Build the query as a string
     * @return string
     */
    public function glue($sep = ' ')
    {
        // SELECT fields and options
        $queryString = 'SELECT' . $sep . $this->selectOptions . implode(',' . $sep, $this->select);

        // FROM tables
        $queryString .= $sep . 'FROM' . $sep . implode(',' . $sep, $this->from);

        // JOIN conditions
        $queryString .= $sep . implode($sep, $this->join);

        // WHERE conditions
        if (is_object($this->where) && $this->where->count()) {
            $queryString .= 'WHERE' . $sep . $this->where->toString();
        }

        // GROUP BY fields
        if (count($this->group)) {
            $queryString .= $sep . 'GROUP BY' . $sep . implode(',' . $sep, $this->group);
        }

        // ORDER BY fields
        if (count($this->order)) {
            $queryString .= $sep . 'ORDER BY' . $sep . implode(',' . $sep, $this->order);
        }

        // LIMIT condition
        if (!empty($this->limit)) {
            $queryString .= $sep . $this->limit;
        }

        return $queryString;
    }
}