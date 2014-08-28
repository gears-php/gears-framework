<?php

namespace Gears\Db\Query;

use Gears\Db\Adapter\AdapterAbstract;

/**
 * Implements the and/or query conditions logic for WHERE clause
 * @author deniskrasilnikov86@gmail.com
 * @package   Gears\Db
 */
abstract class WhereAbstract
{
    /**
     * Operator by which to join conditions (specified in descendant class)
     * @var string
     */
    protected $joinOperator;

    /**
     * Conditions storage
     * @var string
     */
    private $conditions = [];

    /**
     * Db adapter instance
     * @var AdapterAbstract
     */
    private $db = null;

    /**
     * @param AdapterAbstract $db
     */
    public function __construct(AdapterAbstract $db)
    {
        $this->db = $db;
    }

    /**
     * Add condition for query WHERE clause. Takes string condition with optional placeholder parameter values.
     * Alternatively takes WhereAbstract object as for some complex condition to be added.
     * Usage example:
     *
     * <code>
     * $whereOr = new WhereOr($db);
     * $whereOr->add('num > ?', 3);
     * $whereOr->add('sum BETWEEN ? AND ?', 4.8, 7.2);
     *
     * $whereAnd = new WhereAnd($db);
     * $whereAnd->add('1=1');
     * $whereAnd->add($whereOr);
     *
     * $whereAnd->toString(); # (1=1 AND (num > '3' OR sum BETWEEN '4.8' AND '7.2'))
     * </code>
     *
     * @return WhereAbstract
     * @throws \Exception
     */
    public function add()
    {
        $args = func_get_args();

        if (!count($args)) {
            throw new \Exception('add() method should receive at least one argument');
        }

        $condition = array_shift($args);

        // we have just the condition object
        if ($condition instanceof WhereAbstract) {
            $this->conditions[] = $condition;
            return $this;
        }

        // else continue with string condition and passed placeholder values
        if (count($args) != preg_match_all($regex = '/\?/', $condition)) {
            throw new \Exception('Placeholder count does not match the value count');
        }

        $this->conditions[] = preg_replace_callback($regex, function () use (&$args) {
            return $this->db->escape(array_shift($args));
        }, $condition);

        return $this;
    }

    /**
     * Add strict filed-value equality condition
     * @param string|array $field Field or [table => field] pair
     * @param string $value (optional) Field value
     * @return WhereAbstract
     */
    public function eq($field, $value = '?')
    {
        if (is_array($field)) { // table => field pair
            $field = sprintf('%s.%s',
                $this->db->escapeIdentifier(key($field)),
                $this->db->escapeIdentifier(current($field))
            );
        } else {
            $field = $this->db->escapeIdentifier($field);
        }

        if ($value != '?') {
            $value = $this->db->escape($value);
        }

        $this->conditions[] = sprintf('%s=%s', $field, $value);
        return $this;
    }

    /**
     * Add field IN (val, val2, ...) condition
     * @param string $field
     * @param array $values
     * @return WhereAbstract
     */
    public function in($field, array $values)
    {
        foreach ($values as &$value) {
            $value = $this->db->escape($value);
        }

        $this->conditions[] = sprintf('%s IN (%s)', $this->db->escapeIdentifier($field), join(',', $values));
        return $this;
    }

    /**
     * Add a set of equality conditions from the given field => value pairs array
     * @param array $fieldValues
     * @return WhereAbstract
     */
    public function fromArray(array $fieldValues)
    {
        foreach ($fieldValues as $field => $value) {
            $this->eq($field, $value);
        }

        return $this;
    }

    /**
     * Count and return the number of currently added conditions
     * @return integer
     */
    public function count()
    {
        return count($this->conditions);
    }

    /**
     * Build and return conditions SQL string
     */
    public function toString()
    {
        $this->conditions = array_map(function ($condition) {
            return ($condition instanceof WhereAbstract) ? $condition->toString() : $condition;
        }, $this->conditions);

        return '(' . implode(' ' . strtoupper($this->joinOperator) . ' ', $this->conditions) . ')';
    }
}
