<?php

namespace Gears\Framework\Db\Query;

use Gears\Framework\Db\Adapter\AdapterAbstract;

/**
 * Implements the and/or query conditions logic for WHERE clause
 * @author deniskrasilnikov86@gmail.com
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
     * $whereOr->add('id > ?', 5);
     * $whereOr->add('weight BETWEEN ? AND ?', 4.8, 7.2);
     *
     * $whereAnd = new WhereAnd($db);
     * $whereAnd->add('1=1');
     * $whereAnd->add($whereOr);
     *
     * $whereAnd->toString(); # (1=1 AND (id > '5' OR weight BETWEEN '4.8' AND '7.2'))
     * </code>
     *
     * @return $this
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
        $argsCount = count($args);
        if (substr_count($condition, '?') != $argsCount) {
            throw new \Exception('Placeholder count does not match the value count');
        }

        while ($argsCount--) {
            $pos = strpos($condition, '?');
            $condition = substr_replace($condition, $this->db->escape(array_shift($args)), $pos, 1);
        }

        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * Add strict filed-value equality condition
     * @param string|array $field Field or [table => field] pair
     * @param string $value (optional) Field value
     * @return $this;
     */
    public function filter($field, $value = '?')
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

        return '(' . implode($this->joinOperator, $this->conditions) . ')';
    }
}