<?php
/**
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

namespace Gears\Storage;

use Gears\Storage\Reader\Exception\FileNotFound;
use Gears\Storage\Reader\ReaderAbstract;
use Gears\Storage\Reader\Yaml;

/**
 * Simple key-value runtime storage solution
 *
 * @package    Gears\Storage
 */
class Storage implements \ArrayAccess
{
    /**
     * Internal data
     */
    protected array $data = [];

    /**
     * File reader instance
     */
    protected ?ReaderAbstract $reader = null;

    /**
     * Storage can be initialized with a given tree data structure
     */
    public function __construct(array $tree = [])
    {
        $this->data = $tree;
    }

    /**
     * Set file reader
     */
    public function setReader(ReaderAbstract $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Get reader instance. Creates default reader if none exists
     */
    public function getReader(): ReaderAbstract
    {
        if (null === $this->reader) {
            // support yaml files by default
            $this->reader = new Yaml();
        }

        return $this->reader;
    }

    /**
     * Load some data tree from file and store it internally for future usage. Optionally put it into
     * sub-node defined by second parameter. Otherwise, it will fully replace existing internal storage
     * @throws FileNotFound
     */
    public function load(string $file, string $path = null): self
    {
        $loaded = $this->read($file)->raw();

        if (!is_string($path)) {
            $this->data = $loaded;
        } else {
            $this->set($path, $loaded);
        }

        return $this;
    }

    public function raw(): array
    {
        return $this->data;
    }

    /**
     * Read and return the full file data tree or its sub-node. Please note that this function
     * does not save the loaded data node internally. Use {@link load()} for this.
     * @throws FileNotFound
     */
    public function read(string $file, string $path = null): Storage
    {
        $tree = $this->getReader()->read($file);
        $fileExtLength = strlen($fileExt = $this->getReader()->getFileExt()); // load sub files, if any

        array_walk_recursive(
            $tree,
            function (&$item) use ($file, $fileExt, $fileExtLength) {
                if (is_string($item) && substr($item, -$fileExtLength) == $fileExt) {
                    $item = $this->read(dirname($file) . DS . $item);
                }
            }
        );

        return $this->get($path, $tree);
    }

    /**
     * Merge current storage data with the given one. Accepts data tree array | data tree file name | Storage entity to be merged
     */
    public function merge(Storage|array|string $mixed)
    {
        $node = [];

        if (is_array($mixed)) {
            $node = $mixed;
        } elseif (is_string($mixed)) {
            $node = $this->read($mixed)->raw();
        } elseif ($mixed instanceof Storage) {
            $node = $mixed->get()->raw();
        }

        $this->data = array_merge_recursive($this->data, $node);
    }

    /**
     * Get storage node value. If nothing passed returns full storage data tree.
     * If no property found returns NULL. Example:
     *
     * <code>
     * $dbUsername = $storage->get('server.db.username');
     * # which equals to:
     * $cfg = $storage->get();
     * $dbUsername = $cfg['server']['db']['username'];
     * </code>
     *
     * @param string|null $path (optional)  Path to the node separated by dots
     * @param array|null $data (optional)  Custom storage tree to get value from. Inner storage is used by default
     *
     * @return scalar|Storage Found storage node or end-level scalar value
     */
    public function get(string $path = null, mixed $data = null): mixed
    {
        $data = $data ?: $this->data;

        if (!$path) {
            return new Storage($data);
        }

        $p = &$data;
        $p = (array)$p;
        $path = explode('.', trim($path));

        foreach ($path as $node) {
            if (isset($p[$node])) {
                $p = &$p[$node];
            } else {
                $p = null;
            }
        }

        return is_scalar($p) || is_null($p) ? $p : new Storage($p ?? []);
    }

    /**
     * Return a first-level keys of internal storage data
     */
    public function getKeys(): array
    {
        return array_keys($this->data);
    }

    /**
     * Return a first-level node value
     */
    public function __get(string $prop): mixed
    {
        return $this->get($prop);
    }

    /**
     * Set storage node value (new or overwrite existent). Example:
     *
     * <code>
     * $storage->set('server.db.username', 'john doe');
     * # which equals to:
     * $cfg = $storage->get();
     * $cfg['server']['db']['username'] = 'john doe';
     * </code>
     *
     * @param string $path Path to the data tree node separated by dots
     * @param mixed $value
     *
     * @return void
     */
    public function set($path, $value)
    {
        if (trim($path)) {
            $p = &$this->data;
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
     *
     * @param string $path
     *
     * @return void
     */
    public function del($path)
    {
        $p = &$this->data;
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
     *
     * @param string $path
     *
     * @return void
     */
    public function rem($path)
    {
        $this->del($path);
    }

    /**
     * Check whether the storage node by given path exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        $p = &$this->data;
        $p = (array)$p;

        $offset = explode('.', $offset);

        foreach ($offset as $node) {
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
     * @return scalar|Storage
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set storage node value. See {@see set()}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Remove storage node. See {@see del()}
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->del($offset);
    }
}
