<?php
/**
 * @package   Gf
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gf\Db;

/**
 * Object representation of the relation of some particular table to another one
 *
 * @package    Gf
 * @subpackage Database
 */
class TableRelation
{
	/**
	 * Static tables storage per all TableRelation instances
	 * @var array
	 */
	protected static $_tables = [];

	/**
	 * @var Table
	 */
	protected $_table = null;

	/**
	 * Field name of the relation foreign table
	 * @var string
	 */
	protected $_fieldName = 'id';

	/**
	 * Field name of the relation owner table
	 * @var string
	 */
	protected $_foreignName = '';

	/**
	 * Construct table relation from the given metadata
	 */
	public function __construct(Table $owner, $metadata)
	{
		$tableName = $metadata['class'];

		// build full relation table class name using namespace of the relation owner table class
		if (false === strpos($tableName, '\\')) {
			$tableName = preg_replace('/(\w+)$/', ucfirst($tableName), get_class($owner));
		}

		if (!isset(self::$_tables[$tableName])) {
			self::$_tables[$tableName] = new $tableName();
		}

		$this->_table = self::$_tables[$tableName];
		$this->_foreignName = $metadata['foreign'];

		if (isset($metadata['field'])) {
			$this->_fieldName = $metadata['field'];
		}
	}

	public function getTable()
	{
		return $this->_table;
	}

	public function getFieldName()
	{
		return $this->_fieldName;
	}

	public function getForeignName()
	{
		return $this->_foreignName;
	}
}