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
 * Database abstraction layer class. Factory for the specific database adaptors
 * @package    Gf
 * @subpackage Database
 */
class Db
{
	/**
	 * Database adapter instance holder
	 * @var Generic
	 */
	private static $adapter;

	/**
	 * Create database connection using the given connection parameters
	 * @param string $host
	 * @param string $user
	 * @param string $pass
	 * @param string $dbname
	 * @param string $driver
	 * @return Generic
	 */
	public static function connect($host, $user, $pass, $dbname, $driver = 'mysql')
	{
		$className = __NAMESPACE__ . '\\Adapter\\' . ucfirst(strtolower($driver));
		self::$adapter = new $className($host, $user, $pass, $dbname, $driver);
		return self::$adapter;
	}

	/**
	 * Get database connection adapter
	 * @return Generic
	 */
	public static function getAdapter()
	{
		if (null == self::$adapter) {
			throw new \Exception(__CLASS__ . ': no database adapter set. Please check that db connection was successfully established');
		}
		return self::$adapter;
	}
}