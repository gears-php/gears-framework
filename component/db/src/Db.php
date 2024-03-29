<?php
/**
 * @package   Gears\Db
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c) 2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
declare(strict_types=1);

namespace Gears\Db;

use Gears\Db\Adapter\AdapterAbstract;

/**
 * Database abstraction layer class. Factory for the specific database adaptors
 * @package    Gears\Db
 * @subpackage Database
 */
class Db
{
    /**
     * Instantiate new db adapter connection
     * @param array $config Connection properties
     * @param array $options Additional connection options
     * @return AdapterAbstract
     */
    public static function connect(array $config, array $options = []): AdapterAbstract
    {
        $driver = $config['driver'];
        $className = __NAMESPACE__ . '\\Adapter\\' . ucfirst(strtolower($driver));

        return new $className($config, $options);
    }
}
