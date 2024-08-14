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
    protected mixed $data = [];

    /**
     * File reader instance
     */
    protected ?ReaderAbstract $reader = null;

    /**
     * Storage can be initialized with a given data
     */
    public function __construct(mixed $data = null)
    {
        $this->data = $data;
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

    public function raw(): mixed
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
        $fileRealpath = realpath(dirname($file)) . DIRECTORY_SEPARATOR;
        $realFile =  $fileRealpath . basename($file);
        $tree = $this->getReader()->read($realFile);
        $fileExtLen = strlen($fileExt = $this->getReader()->getFileExt());

        array_walk_recursive(
            $tree,
            function (&$item) use ($fileRealpath, $fileExt, $fileExtLen) {
                // load data from another source file when detect special directive (e.g. "@path/to/load_me.ext")
                if (is_string($item) && $item[0] == '@' && substr($item, -$fileExtLen) == $fileExt) {
                    $item = $this->read($fileRealpath . substr($item, 1))->raw();
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

        $this->data = array_replace_recursive($this->data, $node);
    }

    /**
     * Get storage data as new storage object. Primarily used for array-like data structures
     * in order to get some nested level data parts.
     *
     * If path omitted returns NEW storage with full data.
     * If nothing found by path returns NEW empty storage.
     *
     * Example:
     *
     * <code>
     * # get first level data in array-like storage structure
     * $routes = $storage->get('routes');
     * # get 3rd-level nested data in array-like storage structure...
     * $dbUsername = $storage->get('server.db.username');
     * # ...which is same as
     * $cfg = $storage->get()->raw();
     * $dbUsername = $cfg['server']['db']['username'];
     * </code>
     *
     * @param string|null $path (optional) Path to the node separated by dots
     * @param array|null $data (optional) Custom data tree to get value from instead of internal storage data
     */
    public function get(string $path = null, array $data = null): Storage
    {
        $data = $data ?: $this->data;

        if (!$path) {
            return new Storage($data);
        }

        $p = &$data;
        $path = explode('.', trim($path));

        foreach ($path as $node) {
            if (isset($p[$node])) {
                $p = &$p[$node];
            } else {
                $p = null;
            }
        }

        return new Storage($p);
    }

    /**
     * Return a first-level keys of internal storage data
     */
    public function getKeys(): array
    {
        return array_keys($this->data ?: []);
    }

    /**
     * Return a first-level node as NEW storage
     */
    public function __get(string $prop): Storage
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
    public function set(string $path, mixed $value): void
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
     * Delete node from storage
     */
    public function delete(string $path): void
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
     * Wrapper for the {@see delete()}
     */
    public function remove(string $path): void
    {
        $this->delete($path);
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
     * Get storage node RAW value. Also see {@see get()}
     * Example:
     *
     * <code>
     * $dbUsername = $storage['server.db.username'];
     * $dbUsername = $storage['server']['db']['username'];
     * </code>
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset)->raw();
    }

    /**
     * Set storage node value. See {@see set()}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Remove storage node. See {@see delete()}
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->delete($offset);
    }
}
