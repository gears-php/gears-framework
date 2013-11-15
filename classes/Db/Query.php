<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\Db;

use Gears\Framework\Db\Adapter\AdapterAbstract;
use Gears\Framework\Db\Query\ConditionAbstract;

/**
 * Query constructor class
 *
 * @package    Gears\Framework
 * @subpackage Database
 */
class Query
{
    const SORT_ASC = 'ASC';
    const SORT_DESC = 'DESC';

    private $db = null;
    private $selectOptions = '';
    private $select = [];
    private $from = [];
    private $join = [];

    /**
     * @var ConditionAbstract
     */
    private $where = null;

    private $orderBy = [];
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
     *
     *    $query->select('field', 'fieldAlias');
     *    $query->select(array('field1Alias' => 'field1', 'field2', 'field3'));
     *
     * @param string|array $field Field name or array of fields
     * @param string $alias (optional) Field alias
     * @param string $tableName (optional) Table name
     * @return Query
     */
    public function select($field, $alias = null, $tableName = '')
    {
        if (is_array($field)) {
            // array of column fields was passed
            foreach ($field as $fieldKey => $fieldValue) {
                $this->select($fieldValue, $fieldKey, $tableName);
            }
        } else { // add a single column field
            $field = $this->db->escapeIdentifier($field);

            if (is_string($alias) && '' != $alias) {
                $field .= ' AS ' . $this->db->escapeIdentifier($alias);
            }
            if ('' != $tableName) {
                $field = $this->db->escapeIdentifier($tableName) . '.' . $field;
            }

            $this->select[] = $field;
        }
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
     * Add INNER JOIN clause. Note that $joinTable alias (if given) will be used to qualify $joinField.
     * Table name is used otherwise
     * @param string|array $joinTable Joined table name or [alias => name]
     * @param string $joinField Joined table field
     * @param string $baseTable Basic table to join with
     * @param string $baseField Basic table field to join on
     * @return Query
     */
    public function join($joinTable, $joinField, $baseTable, $baseField)
    {
        if (is_array($joinTable)) {
            $joinAlias = array_keys($joinTable)[0];
            $joinTable = $joinTable[$joinAlias];
        } else {
            $joinAlias = $joinTable;
        }

        $this->join[] = sprintf(' INNER JOIN %s AS %s ON %s.%s = %s.%s',
            $this->db->escapeIdentifier($joinTable),
            $this->db->escapeIdentifier($joinAlias),
            $this->db->escapeIdentifier($baseTable),
            $this->db->escapeIdentifier($baseField),
            $this->db->escapeIdentifier($joinAlias),
            $this->db->escapeIdentifier($joinField)
        );
        return $this;
    }

    /**
     * Add a {@see ConditionAbstract} object of WHERE clause
     * @param ConditionAbstract $where
     * @return Query
     */
    public function where(ConditionAbstract $where)
    {
        $this->where = $where;
        return $this;
    }

    /**
     * Get WHERE clause conditions object
     * @return ConditionAbstract
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Add GROUP BY clause field(s)
     * @return Query
     */
    public function groupBy()
    {
        return $this;
    }

    /**
     * Add a single field or array of fields to the ORDER BY clause. Example:
     *
     *    $query->orderBy('lastName', Query::SORT_ASC);
     *    $query->orderBy(array('lastName', 'firstName' => Query::SORT_DESC));
     *
     * @param string|array $field Field name or array of fields
     * @param string $ascDesc (optional) Sort direction
     * @return Query
     */
    public function orderBy($field, $ascDesc = '')
    {
        // array of order by fields was passed
        if (is_array($field)) {
            foreach ($field as $fieldKey => $fieldValue) {
                // numeric element index
                if (is_numeric($fieldKey)) {
                    // just pass field name
                    $this->orderBy($fieldValue);
                } // we have [field name => sort direction] pair
                else {
                    $this->orderBy($fieldKey, $fieldValue);
                }
            }
        } // add a single column field
        else {
            if (null === $field) {
                $this->orderBy[] = 'NULL';
            } else {
                $this->orderBy[] = rtrim($this->db->escapeIdentifier($field) . ' ' . $ascDesc);
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
        $this->limit = sprintf(' LIMIT %d, %d', $offset, $rowCount);
        return $this;
    }

    /**
     * Add SQL_CALC_FOUND_ROWS option. Mysql specific
     */
    public function calcFoundRows()
    {
        $this->selectOptions .= ' SQL_CALC_FOUND_ROWS ';
    }

    /**
     * Execute query
     */
    public function execute()
    {
        return $this->db
            ->prepare($this->toString())
            ->execute()
            ->fetchRows();
    }

    /**
     * Get query SQL string
     * @return string
     */
    public function toString()
    {
        // SELECT fields and options
        $queryString = 'SELECT ' . $this->selectOptions . implode(', ', $this->select);

        // FROM tables
        $queryString .= ' FROM ' . implode(', ', $this->from);

        // JOIN conditions
        $queryString .= implode('', $this->join);

        // WHERE conditions
        if (is_object($this->where) && $this->where->count()) {
            $queryString .= ' WHERE ' . $this->where->toString();
        }

        // GROUP BY fields

        // ORDER BY fields
        if (count($this->orderBy)) {
            $queryString .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        // LIMIT condition
        if (!empty($this->limit)) {
            $queryString .= $this->limit;
        }

        return $queryString;
    }
}






