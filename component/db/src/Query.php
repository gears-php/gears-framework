<?php

/**
 * @package   Gears\Db
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c) 2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
declare(strict_types=1);

namespace Gears\Db;

use Gears\Db\Query\WhereAnd;
use Gears\Db\Query\WhereOr;

/**
 * Query constructor class
 * @package Gears\Db
 */
class Query
{
    # sort direction constants
    public const ASC = 'ASC';
    public const DESC = 'DESC';

    private string $selectOptions = '';
    protected array $select = [];
    private array $from = [];
    private array $join = [];
    private ?WhereAnd $where;
    private array $group = [];
    private array $order = [];
    private ?string $limit = null;
    private array $params = [];

    /**
     * Init query with the db adapter instance
     */
    public function __construct(protected Db $db)
    {
    }

    /**
     * Add a single field or array of fields to the SELECT clause
     * <code>
     * $query->select('field', 'fieldAlias');
     * $query->select(['field1Alias' => 'field1', 'field2', 'field3']);
     * </code>
     *
     * @param array|string $field Field name or array of fields
     * @param string|null $alias (optional) Field alias
     * @param string $table (optional) Table name
     */
    public function select(array|string $field, string $alias = null, string $table = ''): static
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
     */
    public function selectCount(string $field, string $alias = null, string $table = null): static
    {
        $this->selectSingle(['count' => $field], $alias, $table);

        return $this;
    }

    /**
     * Add a single field to SELECT clause
     * @param array|string $field Field name or [aggregate_function => field] mapping
     * @param string|null $alias (optional) Field alias
     * @param string|null $table (optional) Table name
     */
    public function selectSingle(array|string $field, string $alias = null, string $table = null): static
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
     */
    public function selectAll(): static
    {
        $this->select[] = '*';

        return $this;
    }

    /**
     * Remove all SELECT clause fields
     */
    public function noSelect(): static
    {
        $this->select = [];

        return $this;
    }

    /**
     * Add FROM table
     */
    public function from(string $table, string $alias = null): static
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
     */
    public function noFrom(): static
    {
        $this->from = [];

        return $this;
    }

    /**
     * Add JOIN clause. Note that $joinTable alias (if given) will be used to qualify $joinField.
     * Table name is used otherwise. By default,INNER JOIN is applied.
     *
     * @param array|string $joinTable Joined table name or [alias => name]
     * @param string $joinField Joined table field
     * @param string $baseTable Basic table to join with
     * @param string $baseField Basic table field to join on
     * @param string $type (optional) Join type
     */
    public function join(
        array|string $joinTable,
        string $joinField,
        string $baseTable,
        string $baseField,
        string $type = 'inner'
    ): static {
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
     * @param array|string $joinTable Joined table name or [alias => name]
     * @param string $joinField Joined table field
     * @param string $baseTable Basic table to join with
     * @param string $baseField Basic table field to join on
     */
    public function leftJoin(array|string $joinTable, string $joinField, string $baseTable, string $baseField): static
    {
        $this->join($joinTable, $joinField, $baseTable, $baseField, 'left');

        return $this;
    }

    /**
     * Remove all JOIN clauses
     */
    public function noJoins(): static
    {
        $this->join = [];

        return $this;
    }

    /**
     * Add a WHERE object clause
     */
    public function where(WhereAnd $where): static
    {
        $this->where = $where;

        return $this;
    }

    /**
     * Get WHERE clause conditions object
     */
    public function getWhere(): ?WhereAnd
    {
        return $this->where;
    }

    /** @see WhereAnd::add() */
    public function andWhere(...$args): static
    {
        $this->where->add(...$args);

        return $this;
    }

    /**
     * Remove WHERE clause object
     */
    public function noWhere(): static
    {
        $this->where = null;

        return $this;
    }

    /**
     * Add GROUP BY clause field(s)
     */
    public function group($field, string $table = null): static
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
     * $query->order('lastName', 'tableName', Query::ASC);
     * $query->order(['lastName', 'firstName' => Query::DESC]);
     * $query->order('NULL'); # ORDER BY NULL
     * </code>
     * @param array|string $field Field name or array of fields to order
     * @param string $sort (optional) Sort direction
     * @param string $tableAlias (optional) Table name alias
     */
    public function order(array|string $field, string $tableAlias, string $sort = self::ASC): static
    {
        if (is_array($field)) { // array of order-by fields
            foreach ($field as $fieldKey => $fieldValue) {
                if (is_numeric($fieldKey)) {
                    // for numeric index just pass field name
                    $this->order($fieldValue, $tableAlias);
                } else { // we have [field name => sort direction] pair
                    $this->order($fieldKey, $tableAlias, $fieldValue);
                }
            }
        } elseif (strtolower($field) == 'null') { // add explicit NULL
            $this->order[] = 'NULL';
        } else {  // add a single column field
            $this->order[] = rtrim(
                $this->db->escapeIdentifier($tableAlias) . '.'
                . $this->db->escapeIdentifier($field) . ' '
                . strtoupper($sort)
            );
        }

        return $this;
    }

    /**
     * Remove all GROUP BY clause conditions
     */
    public function noGroup(): static
    {
        $this->group = [];

        return $this;
    }

    /**
     * Remove all ORDER BY clause conditions
     */
    public function noOrder(): static
    {
        $this->order = [];

        return $this;
    }

    /**
     * Add selection limit clause
     */
    public function limit(int $count, int $offset = 0): static
    {
        $this->limit = $this->db->getLimitClause($count, $offset);

        return $this;
    }

    /**
     * Remove LIMIT clause
     */
    public function noLimit(): static
    {
        $this->limit = null;

        return $this;
    }

    /**
     * Bind query parameter to a specific positional placeholder
     */
    public function bind(int $idx, mixed $value): static
    {
        $this->params[$idx] = $value;

        return $this;
    }

    /**
     * Execute query and return db adapter
     */
    public function exec(): Db
    {
        if (!count($this->select)) {
            $this->selectAll();
        }

        return $this->db->query($this, $this->params);
    }

    /**
     * Get query SQL string
     */
    public function toString(): string
    {
        return $this->glue();
    }

    /**
     * Wrapper for the {@see toString()}
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Build the query as a string. Query parts are imploded by a given separator which defaults to a single space line.
     */
    public function glue(string $sep = ' '): string
    {
        // SELECT fields and options
        $queryString = 'SELECT' . $sep . $this->selectOptions . implode(',' . $sep, $this->select);

        // FROM tables
        $queryString .= $sep . 'FROM' . $sep . implode(',' . $sep, $this->from);

        // JOIN conditions
        $queryString .= $sep . implode($sep, $this->join);

        // WHERE conditions
        if (is_object($this->where) && $this->where->count()) {
            $queryString .= $sep . 'WHERE' . $sep . $this->where->toString();
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
