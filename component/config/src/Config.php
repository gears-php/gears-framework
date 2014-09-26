<?php
/**
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Config;

use Gears\Config\Reader\ReaderAbstract;
use Gears\Config\Reader\Yaml;

/**
 * Storage for various configuration tree nodes used by core app and other services
 * @package    Gears\Config
 */
class Config implements \ArrayAccess
{
    /**
     * Configuration internal storage
     * @var array
     */
    protected $storage = null;

    /**
     * Configuration file reader instance
     * @var ReaderAbstract
     */
    protected $reader = null;

    /**
     * Config can be initialized with a given tree configuration array
     * for further manipulations
     * @param array (optional) $tree
     */
    public function __construct($tree = null)
    {
        $this->storage = $tree;
    }

    /**
     * Set config files reader
     * @param ReaderAbstract $reader
     */
    public function setReader(ReaderAbstract $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Get reader instance. Creates default reader if none exists
     * @return ReaderAbstract Config reader object
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
     * Load some configuration tree from file and store it internally for future usage. Optionally
     * put it into sub-node defined by second parameter. Otherwise it will fully replace existing
     * internal storage
     * @param string $file
     * @param string (optional) $path Dot separated path of the node under which to store the loaded config
     * @return array Loaded configuration tree
     */
    public function load($file, $path = null)
    {
        $loaded = $this->read($file);

        if (!is_string($path)) {
            $this->storage = $loaded;
        } else {
            $this->set($path, $loaded);
        }

        return $loaded;
    }

    /**
     * Same as {@see read()} but returns a Config instance
     * @param string $file
     * @param string (optional) $node
     * @return Config
     */
    public function readObj($file, $node = null)
    {
        return new Config($this->read($file, $node));
    }

    /**
     * Read and return the full file configuration tree or its sub-node. Please note that this function
     * does not save the loaded config / sub-node internally. Use {@link load()} for this
     * @param string $file Path to the file
     * @param string (optional) $path Dot separated path to the sub-node to be returned
     * @return array Configuration tree
     */
    public function read($file, $path = null)
    {
        $tree = $this->getReader()->read($file);
        $fileExtLength = strlen($fileExt = $this->getReader()->getFileExt()); // load sub files, if any

        array_walk_recursive($tree, function (&$item) use ($file, $fileExt, $fileExtLength) {
            if (is_string($item) && substr($item, -$fileExtLength) == $fileExt) {
                $item = $this->read(dirname($file) . DS . $item);
            }
        });

        return $tree ? $this->get($path, $tree) : [];
    }

    /**
     * Merge current configuration with the given one
     * @param array|string|Config Configuration array or config file name or Config entity to be merged
     */
    public function merge($config)
    {
        $node = [];

        if (is_array($config)) {
            $node = $config;
        } elseif (is_string($config)) {
            $node = $this->read($config);
        } elseif($config instanceof Config) {
            $node = $config->get();
        }

        $this->storage = array_merge_recursive($this->storage, $node);
    }

    /**
     * Same as {@link get()} but returns a new Config instance
     * @param string $path
     * @param array (optional) $storage
     * @return Config
     */
    public function getObj($path, $storage = null)
    {
        return new Config($this->get($path, $storage));
    }

    /**
     * Get some configuration property. If nothing passed returns full configuration tree.
     * If no property found returns NULL. Example:
     * <code>
     * $dbUsername = $cfg->get('server.db.username');
     * # which equals to:
     * $cfg = $cfg->get();
     * $dbUsername = $cfg['server']['db']['username'];
     * </code>
     * @param string (optional) $path Path to the property inside configuration tree, separated by dots
     * @param array (optional) $storage Storage from where to get property. Inner class storage by default
     * @return array|mixed|NULL Full configuration tree | found configuration property value | NULL if nothing is found
     */
    public function get($path = null, $storage = null)
    {
        $storage = $storage ? : $this->storage;

        if (trim($path)) {
            $p = & $storage;
            $p = (array)$p;
            $path = explode('.', $path);

            foreach ($path as $node) {
                if (isset($p[$node])) {
                    $p = & $p[$node];
                } else $p = null;
            }

            return $p;
        }

        return $storage;
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
     * Set some configuration property (new or overwrite existent). Example:
     * <code>
     * $cfg->set('server.db.username', 'john doe');
     * # which equals to:
     * $cfg = $cfg->get();
     * $cfg['server']['db']['username'] = 'john doe';
     * </code>
     * @param string $path Path to the property inside configuration tree, separated by dots
     * @param mixed $value
     * @return void
     */
    public function set($path, $value)
    {
        if (trim($path)) {
            $p = & $this->storage;
            $p = (array)$p;
            $path = explode('.', $path);

            foreach ($path as $node) {
                $p = & $p[$node];
            }

            $p = $value;
        }
    }

    /**
     * Remove configuration tree node
     * @param string $path
     * @return void
     */
    public function del($path)
    {
        $p = & $this->storage;
        $p = (array)$p;
        $path = explode('.', $path);
        $nodeCount = count($path);

        while (--$nodeCount) {
            $node = array_shift($path);

            if (isset($p[$node])) {
                $p = & $p[$node];
            }
        }

        if (is_array($p)) {
            unset($p[array_shift($path)]);
        }
    }

    /**
     * Wrapper for the {@see del()}
     * @param string $path
     * @return void
     */
    public function rem($path)
    {
        $this->del($path);
    }

    /**
     * Check whether the configuration tree property exists
     * @param string $path Dot separated path to the property inside configuration tree
     * @return boolean
     */
    public function offsetExists($path)
    {
        $p = & $this->storage;
        $p = (array)$p;

        $path = explode('.', $path);

        foreach ($path as $node) {
            if (isset($p[$node])) {
                $p = & $p[$node];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Get configuration property. See {@see get()}
     * @param string $path
     * @return mixed
     */
    public function offsetGet($path)
    {
        return $this->get($path);
    }

    /**
     * Set configuration property. See {@see set()}
     * @param string $path
     * @param mixed $value
     * @return void
     */
    public function offsetSet($path, $value)
    {
        $this->set($path, $value);
    }

    /**
     * Remove configuration tree node. See {@see delete()}
     * @param string $path
     * @return void
     */
    public function offsetUnset($path)
    {
        $this->del($path);
    }
}
