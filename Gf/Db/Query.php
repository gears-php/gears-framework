<?php
/**
 * @package   Gf
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gf\Db;

use Gf\Db\Adapter\Generic;

/**
 * Query constructor class
 *
 * @package    Gf
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
	private $where = null;
	private $orderBy = [];
	private $limit = null;

	/**
	 * Init query with the db adapter instance
	 * @param Generic $db
	 */
	public function __construct(Generic $db)
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
	 * @param array $joinBase Basic [table => field] to join with
	 * @return Query
	 */
	public function join($joinTable, $joinField, $joinBase)
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
			$this->db->escapeIdentifier(key($joinBase)),
			$this->db->escapeIdentifier(reset($joinBase)),
			$this->db->escapeIdentifier($joinAlias),
			$this->db->escapeIdentifier($joinField)
		);
		return $this;
	}

	/**
	 * Add a {@link QueryCondition} object of WHERE clause
	 * @param QueryCondition $where
	 * @return Query
	 */
	public function where(QueryCondition $where)
	{
		$this->where = $where;
		return $this;
	}

	/**
	 * Get WHERE clause conditions object
	 * @return QueryCondition
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
	 * @return \Gf\Db\Query
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
	 * Execute query using db adapter set via constructor
	 */
	public function execute()
	{
		if ($this->db instanceof Generic) {
			return $this->db->query($this->toString())->fetchRows();
		} else {
			throw new \Exception("Query db adapter is not set");
		}
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

/**
 * Implements the and/or query conditions logic for WHERE clause
 */
abstract class QueryCondition
{
	/**
	 * Operator by which to join conditions (specified in descendant class)
	 * @var string
	 */
	protected $_joinOperator;

	/**
	 * Conditions storage
	 * @var string
	 */
	private $_conditions = [];

	/**
	 * Add query condition. Takes two string parameters as for the left and right operands of
	 * simple string condition. Alternatively takes a single QueryCondition object parameter as
	 * for some complex condition to be added.
	 *
	 * ! IMPORTANT The auto-escaping of passed filter identifier and value is not currently supported. You
	 * should do this manually
	 *
	 * @param string|QueryCondition $leftOperand Identifier or QueryCondition object
	 * @param string $rightOperand (optional) Filtering value
	 */
	public function add($leftOperand, $rightOperand = '?')
	{
		if ($leftOperand instanceof QueryCondition) {
			$this->_conditions[] = $leftOperand;
		} else {
			 // @todo Think of how to pass db adapter instance to be used for auto-escaping
			$this->_conditions[] = sprintf('%s=%s', $leftOperand, $rightOperand);
		}
	}

	/**
	 * Count and return the number of currently added conditions
	 * @return integer
	 */
	public function count()
	{
		return count($this->_conditions);
	}

	/**
	 * Build and return conditions SQL string
	 */
	public function toString()
	{
		$this->_conditions = array_map(function ($condition) {
			return ($condition instanceof QueryCondition) ? $condition->toString() : $condition;
		}, $this->_conditions);

		return '(' . implode($this->_joinOperator, $this->_conditions) . ')';
	}
}

class QueryConditionAnd extends QueryCondition
{
	protected $_joinOperator = ' AND ';
}

class QueryConditionOr extends QueryCondition
{
	protected $_joinOperator = ' OR ';
}