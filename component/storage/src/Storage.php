<?php
/**
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Storage;

use Gears\Storage\Reader\ReaderAbstract;
use Gears\Storage\Reader\Yaml;

/**
 * A general-purpose runtime storage solution
 * @package    Gears\Storage
 */
class Storage implements \ArrayAccess
{
    /**
     * Internal storage
     * @var array
     */
    protected $storage;

    /**
     * File reader instance
     * @var ReaderAbstract
     */
    protected $reader;

    /**
     * Storage can be initialized with a given tree data structure
     * @param array (optional) $tree
     */
    public function __construct($tree = null)
    {
        $this->storage = $tree;
    }

    /**
     * Set file reader
     * @param ReaderAbstract $reader
     */
    public function setReader(ReaderAbstract $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Get reader instance. Creates default reader if none exists
     * @return ReaderAbstract File reader object
     */
    public function getReader()
    {
        if (null === $this->reader) {
            // support yaml files by default
            $this->reader = new Yaml();
        }
        return $this->reader;
    }

    /**
     * Load some data tree from file and store it internally for future usage. Optionally put it into
     * sub-node defined by second parameter. Otherwise it will fully replace existing internal storage
     * @param string $file
     * @param string (optional) $path Dot separated path of the node under which to store the loaded data
     * @return array Loaded data tree
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
     * Same as {@see read()} but returns a storage instance
     * @param string $file
     * @param string (optional) $node
     * @return Storage
     */
    public function readObj($file, $node = null)
    {
        return new Storage($this->read($file, $node));
    }

    /**
     * Read and return the full file data tree or its sub-node. Please note that this function
     * does not save the loaded data node internally. Use {@link load()} for this.
     * @param string $file Path to the file
     * @param string (optional) $path Dot separated path to the sub-node to be returned
     * @return array Data tree
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
     * Merge current storage data with the given one
     * @param array|string|Storage $mixed Data tree | data tree file name | Storage entity to be merged
     */
    public function merge($mixed)
    {
        $node = [];

        if (is_array($mixed)) {
            $node = $mixed;
        } elseif (is_string($mixed)) {
            $node = $this->read($mixed);
        } elseif ($mixed instanceof Storage) {
            $node = $mixed->get();
        }

        $this->storage = array_merge_recursive($this->storage, $node);
    }

    /**
     * Same as {@link get()} but returns a new storage instance
     * @param string $path
     * @param array (optional) $storage
     * @return Storage
     */
    public function getObj($path, $storage = null)
    {
        return new Storage($this->get($path, $storage));
    }

    /**
     * Get storage node value. If nothing passed returns full storage data tree.
     * If no property found returns NULL. Example:
     * <code>
     * $dbUsername = $storage->get('server.db.username');
     * # which equals to:
     * $cfg = $storage->get();
     * $dbUsername = $cfg['server']['db']['username'];
     * </code>
     * @param string (optional) $path Path to the node separated by dots
     * @param array (optional) $storage Custom storage tree to get value from. Inner storage is used by default
     * @return array|mixed|NULL Full storage data tree | found storage node value | NULL if nothing is found
     */
    public function get($path = null, $storage = null)
    {
        $storage = $storage ?: $this->storage;

        if (trim($path)) {
            $p = &$storage;
            $p = (array)$p;
            $path = explode('.', $path);

            foreach ($path as $node) {
                if (isset($p[$node])) {
                    $p = &$p[$node];
                } else {
                    $p = null;
                };
            }

            return $p;
        }

        return $storage;
    }

    /**
     * Return a first-level node value
     * @param string $prop
     * @return mixed
     */
    public function __get($prop)
    {
        return $this->get($prop);
    }

    /**
     * Set storage node value (new or overwrite existent). Example:
     * <code>
     * $storage->set('server.db.username', 'john doe');
     * # which equals to:
     * $cfg = $storage->get();
     * $cfg['server']['db']['username'] = 'john doe';
     * </code>
     * @param string $path Path to the data tree node separated by dots
     * @param mixed $value
     * @return void
     */
    public function set($path, $value)
    {
        if (trim($path)) {
            $p = &$this->storage;
            $p = (array)$p;
            $path = explode('.', $path);

            foreach ($path as $node) {
                $p = &$p[$node];
            }

            $p = $value;
        }
    }

    /**
     * Remove node from storage
     * @param string $path
     * @return void
     */
    public function del($path)
    {
        $p = &$this->storage;
        $p = (array)$p;
        $path = explode('.', $path);
        $nodeCount = count($path);

        while (--$nodeCount) {
            $node = array_shift($path);

            if (isset($p[$node])) {
                $p = &$p[$node];
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
     * Check whether the storage node exists
     * @param string $path Dot separated path to the node inside the storage data tree
     * @return boolean
     */
    public function offsetExists($path)
    {
        $p = &$this->storage;
        $p = (array)$p;

        $path = explode('.', $path);

        foreach ($path as $node) {
            if (isset($p[$node])) {
                $p = &$p[$node];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Get storage node value. See {@see get()}
     * @param string $path
     * @return mixed
     */
    public function offsetGet($path)
    {
        return $this->get($path);
    }

    /**
     * Set storage node value. See {@see set()}
     * @param string $path
     * @param mixed $value
     * @return void
     */
    public function offsetSet($path, $value)
    {
        $this->set($path, $value);
    }

    /**
     * Remove storage node. See {@see del()}
     * @param string $path
     * @return void
     */
    public function offsetUnset($path)
    {
        $this->del($path);
    }
}
