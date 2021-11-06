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
    const ASC = 'ASC';
    const DESC = 'DESC';

    /**
     * @var AdapterAbstract
     */
    protected $db;

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
    private $where;

    /**
     * @var array
     */
    private $group = [];

    /**
     * @var array
     */
    private $order = [];

    /**
     * @var string
     */
    private $limit;

    /**
     * Query parameters
     * @var array
     */
    private $params = [];

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
     * @return $this
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
     * @return $this
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
     * @return $this
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

        $field = $field == '*' ? $field : $this->db->escapeIdentifier($field);

        if (!empty($table)) {
            $field = $this->db->escapeIdentifier($table) . '.' . $field;
        }

        $field = sprintf($fn, $field);

        if (!empty($alias)) {
            $field .= ' AS ' . $this->db->escapeIdentifier($alias);
        }

        $this->select[] = $field;

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
     * Remove all SELECT clause fields
     * @return $this
     */
    public function noSelect()
    {
        $this->select = [];

        return $this;
    }

    /**
     * Add FROM table
     * @param $table
     * @param string $alias
     * @return $this
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
     * Remove all FROM clause tables
     * @return $this
     */
    public function noFrom()
    {
        $this->from = [];

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
     * @return $this
     */
    public function join($joinTable, $joinField, $baseTable, $baseField, $type = 'inner')
    {
        if (is_array($joinTable)) {
            $joinAlias = key($joinTable);
            $joinTable = current($joinTable);
        } else {
            $joinAlias = $joinTable;
        }

        $this->join[] = sprintf(
            '%s JOIN %s AS %s ON %s.%s = %s.%s',
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
     * @return $this
     */
    public function leftJoin($joinTable, $joinField, $baseTable, $baseField)
    {
        $this->join($joinTable, $joinField, $baseTable, $baseField, 'left');

        return $this;
    }

    /**
     * Remove all JOIN clauses
     * @return $this
     */
    public function noJoins()
    {
        $this->join = [];

        return $this;
    }

    /**
     * Add a {@see WhereAbstract} object of WHERE clause
     * @param WhereAbstract $where
     * @return $this
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
     * Remove WHERE clause object
     * @return $this
     */
    public function noWhere()
    {
        $this->where = null;

        return $this;
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
     * Remove all GROUP BY clause conditions
     * @return $this
     */
    public function noGroup()
    {
        $this->group = [];

        return $this;
    }

    /**
     * Add a single field or array of fields to the ORDER BY clause. Example:
     * <code>
     * $query->order('lastName', Query::ASC);
     * $query->order(['lastName', 'firstName' => Query::DESC]);
     * </code>
     * @param string|array $field Field name or array of fields to order
     * @param string $sort (optional) Sort direction
     * @return $this
     */
    public function order($field, $sort = self::ASC)
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
     * Remove all ORDER BY clause conditions
     * @return $this
     */
    public function noOrder()
    {
        $this->order = [];

        return $this;
    }

    /**
     * Add selection limit clause
     * @param int $offset
     * @param int $rowCount
     * @return $this
     */
    public function limit(int $offset, int $rowCount)
    {
        // @todo move the limit clause to each specific db adapter implementation: $db->getLimitClause()
        $this->limit = sprintf('LIMIT %d,%d', $offset, $rowCount);

        return $this;
    }

    /**
     * Remove LIMIT clause
     * @return $this
     */
    public function noLimit()
    {
        $this->limit = null;

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
     * Bind query parameter to a specific positional placeholder
     * @param int $idx
     * @param mixed $value
     * @return $this
     */
    public function bind(int $idx, $value)
    {
        $this->params[$idx] = $value;

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

        return $this->db->query($this, $this->params);
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
     * @param string $sep Query chunks separator
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
        if ($this->limit) {
            $queryString .= $sep . $this->limit;
        }

        return $queryString;
    }
}
