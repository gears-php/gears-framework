<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\App\Config;

use Gears\Framework\App\Config\Reader\IReader;
use Gears\Framework\App\Config\Reader\Yaml;

/**
 * Storage for various configuration tree nodes used by core app and other services
 * @package    Gears\Framework
 * @subpackage App
 */
class Config
{
    /**
     * Configuration internal storage
     * @var array
     */
    protected $storage = null;

    /**
     * Configuration file reader instance
     * @var IReader
     */
    protected $reader = null;

    /**
     * Config can be initialized with a given tree configuration array
     * for further manipulations
     * @param array (optional) $node
     */
    public function __construct($node = null)
    {
        $this->storage = $node;
    }

    /**
     * Return a first-level node value from current configuration
     * @param string $prop
     * @return mixed
     */
    public function __get($prop)
    {
        return $this->get($prop);
    }

    /**
     * Set config files reader
     * @param IReader $reader
     */
    public function setReader(IReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Get reader instance. Creates default reader if none exists
     * @return IReader Config reader object
     */
    public function getReader()
    {
        if (null === $this->reader) {
            // support yaml configuration files by default
            $this->reader = new Yaml();
        }
        return $this->reader;
    }

    /**
     * Read and return the full file configuration tree or its sub-node. Please note that this function
     * does not saves the loaded config / sub-node internally. Use {@link load()} for this
     * @param string $file Path to the file
     * @param string (optional) $node Sub-node to be returned
     * @return array Configuration tree
     * @throws \Exception If config file is not found
     */
    public function read($file, $node = null)
    {
        if (is_file($file)) {
            $loaded = $this->getReader()->getFileConfig($file);

            // load sub files, if any
            $fileExtLength = strlen($this->reader->getFileExt());
            array_walk_recursive($loaded, function (&$item) use ($file, $fileExtLength) {
                if (is_string($item) && substr($item, -$fileExtLength) == $this->reader->getFileExt()) {
                    $item = $this->read(dirname($file) . DS . $item);
                }
            });

            return $loaded ? $this->get($node, $loaded) : [];
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
    public function load($file, $node = null)
    {
        $loaded = $this->read($file);

        if (!is_string($node)) {
            $this->storage = $loaded;
        } else {
            $this->set($node, $loaded);
        }

        return $loaded;
    }

    /**
     * Same as {@link get()} but returns a new Config instance 
     * @param string $node
     * @param null (optional) $storage
     * @return Config
     */
    public function getObj($node, $storage = null)
    {
        return new Config($this->get($node, $storage));
    }

    /**
     * Get some configuration property. If nothing passed returns full configuration tree.
     * If no property found returns NULL. Example:
     *
     * $dbUsername = $cfg->get('server.db.username');
     * # which equals to:
     * $cfg = $cfg->get();
     * $dbUsername = $cfg['server']['db']['username'];
     *
     * @param string (optional) $node Path to the property inside configuration tree, separated by dots
     * @param array (optional) $storage Storage from where to get property. Inner class storage by default
     * @return array|mixed|NULL Full configuration tree | found configuration property value | NULL if nothing is found
     */
    public function get($node = null, $storage = null)
    {
        $storage = $storage ? : $this->storage;

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
     * $success = $cfg->set('server.db.username', 'john doe');
     * # which equals to:
     * $cfg = $cfg->get();
     * $cfg['server']['db']['username'] = 'john doe';
     *
     * @param string $node Path to the property inside configuration tree, separated by dots
     * @param mixed $value
     * @return boolean Whether value was set or not
     */
    public function set($node, $value)
    {
        if ('' == trim($node)) {
            return false;
        } else {
            $p = & $this->storage;
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