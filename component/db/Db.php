<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\Db;

use Gears\Framework\Db\Adapter\AdapterAbstract;

/**
 * Database abstraction layer class. Factory for the specific database adaptors
 * @package    Gears\Framework
 * @subpackage Database
 */
class Db
{
    /**
     * Create database connection using the given connection parameters
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $dbname
     * @param string $driver
     * @return AdapterAbstract
     */
    public static function connect($host, $user, $pass, $dbname, $driver)
    {
        if (null === $driver) {
            throw new \Exception('Db connection driver is not defined');
        }
        $className = __NAMESPACE__ . '\\Adapter\\' . ucfirst(strtolower($driver));
        return new $className($host, $user, $pass, $dbname, $driver);
    }
}