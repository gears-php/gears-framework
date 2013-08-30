<?php
/**
 * @author deniskrasilnikov86@gmail.com
 */
namespace Gears\Framework\Db\Adapter;

use Gears\Framework\Db\Adapter\Generic;

/**
 * Implements database adapter functionality specific to MySQL db storage
 * @package Gears\Framework\Db\Adapter
 */
class Mysql extends Generic
{
	protected $driver = 'mysql';

	public function escapeIdentifier($identifier)
	{
		return "`" . str_replace('`', '``', $identifier) . "`";
	}

	public function getLastRowCount()
	{
		return intval($this->conn->query('SELECT FOUND_ROWS()')->fetchColumn());
	}

	protected function getPlaceholderIgnoreRegex()
	{
		return '
            "   (?> [^"\\\\]+|\\\\"|\\\\)*    "   |
            \'  (?> [^\'\\\\]+|\\\\\'|\\\\)* \'   |
            `   (?> [^`]+ | ``)*              `   |   # backticks
            /\* .*?                          \*/      # comments
        ';
	}
}