<?php

namespace Gears\Framework\Db\Query;

use Gears\Framework\Db\Adapter\AdapterAbstract;

/**
 * Implements the and/or query conditions logic for WHERE clause
 */
abstract class ConditionAbstract
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
     * Add query condition for WHERE clause. Takes two string parameters as for the left and right operands of
     * simple equality condition. Alternatively takes a ConditionAbstract object parameter as
     * for some complex condition to be added.
     * @param string|array|ConditionAbstract $left Field or [table => field] pair or ConditionAbstract object
     * @param string $right (optional) Field value
     */
    public function add($left, $right = '?')
    {
        if ($left instanceof ConditionAbstract) {
            $this->conditions[] = $left;
        } else {
            if (is_array($left)) { // table => field pair
                $left = sprintf('%s.%s',
                    $this->db->escapeIdentifier(key($left)),
                    $this->db->escapeIdentifier(current($left))
                );
            } else {
                $left = $this->db->escapeIdentifier($left);
            }

            if ($right != '?') {
                $right = $this->db->escape($right);
            }
            $this->conditions[] = sprintf('%s=%s', $left, $right);
        }
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
            return ($condition instanceof ConditionAbstract) ? $condition->toString() : $condition;
        }, $this->conditions);

        return '(' . implode($this->joinOperator, $this->conditions) . ')';
    }
}
