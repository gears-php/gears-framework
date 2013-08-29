<?php
/**
 * @package   Gf
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gf\Core\Config;

use Gf\Core\Config\Reader\IReader;
use Gf\Core\Config\Reader\Yaml;

/**
 * Storage for various configuration tree nodes used by core app and other services
 * @package    Gf
 * @subpackage Core
 */
class Config
{
    /**
     * Configuration internal storage
     * @var array
     */
    protected static $storage = [];

    /**
     * Configuration file reader instance
     * @var IReader
     */
    protected static $reader = null;

    /**
     * Set config files reader
     * @param IReader $reader
     */
    public static function setReader(IReader $reader)
    {
        self::$reader = $reader;
    }

    /**
     * Get reader instance. Creates default reader if none exists
     * @return IReader Config reader object
     */
    public static function getReader()
    {
        if (null === self::$reader) {
            // support yaml configuration files by default
            self::$reader = new Yaml();
        }
        return self::$reader;
    }

    /**
     * Read and return the full file configuration tree or its sub-node. Please note that this function
     * does not saves the loaded config / sub-node internally. Use {@link load()} for this
     * @param string $file Path to the file
     * @param string (optional) $node Sub-node to be returned
     * @return array Configuration tree
     */
    public static function read($file, $node = null)
    {
        if (is_file($file)) {
            $loaded = self::getReader()->getFileConfig($file);

            // load sub files, if any
            $fileExtLength = strlen(self::$reader->getFileExt());
            array_walk_recursive($loaded, function (&$item) use ($file, $fileExtLength) {
                if (is_string($item) && substr($item, -$fileExtLength) == self::$reader->getFileExt()) {
                    $item = self::read(dirname($file) . DS . $item);
                }
            });

            return $loaded ? self::get($node, $loaded) : [];
        } else {
            throw new \Exception('Config file not found: ' . $file);
        }
    }

    /**
     * Load some configuration tree from file and store it internally for future usage. Optionally
     * put it into sub-node given as second parameter. Otherwise it will fully replace existing
     * internal storage
     * @param string $file
     * @param string (optional) $node Dot separated path of the node under which to store the loaded config
     * @return array Loaded configuration tree
     */
    public static function load($file, $node = null)
    {
        $loaded = self::read($file);

        if (!is_string($node)) {
            self::$storage = $loaded;
        } else {
            self::set($node, $loaded);
        }

        return $loaded;
    }

    /**
     * Get some configuration property. If nothing passed returns full configuration tree.
     * If no property found returns NULL. Example:
     *
     * $dbUsername = Config::get('server.db.username'); 
     * # which equals to:
     * $cfg = Config::get();
     * $dbUsername = $cfg['server']['db']['username'];
     *
     * @param string (optional) $node Path to the property inside configuration tree, separated by dots
     * @param array (optional) $storage Storage from where to get property. Inner class storage by default
     * @return array|mixed|NULL Full configuration tree | found configuration property value | NULL if nothing is found
     */
    public static function get($node = '', $storage = null)
    {
        $storage = $storage ? : self::$storage;

        if ('' == trim($node)) {
            return $storage;
        } else {
            $p = & $storage;
            $p = (array)$p;

            $path = explode('.', $node);

            foreach ($path as $node) {
                if (isset($p[$node])) {
                    $p = & $p[$node];
                } else $p = null;
            }

            return $p;
        }
    }

    /**
     * Set some configuration property (new or overwrite existent). Example:
     *
     * $success = Config::set('server.db.username', 'johndoe');
     * # which equals to:
     * $cfg = Config::get();
     * $cfg['server']['db']['username'] = 'johndoe';
     *
     * @param string $node Path to the propery inside configuration tree, separated by dots
     * @param mixed $value
     * @return boolean Whether value was set or not
     */
    public static function set($node, $value)
    {
        if ('' == trim($node)) {
            return false;
        } else {
            $p = & self::$storage;
            $p = (array)$p;

            $path = explode('.', $node);

            foreach ($path as $node) {
                $p = & $p[$node];
            }

            $p = $value;

            return true;
        }
    }
}