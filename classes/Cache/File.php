<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\Cache;

/**
 * File system cache implementation
 * @package    Gears\Framework
 * @subpackage Cache
 */
class File implements ICache
{
    /**
     * Cache expiration time
     */
    private $expireTimeSeconds = 900; // 15 minutes

    /**
     * Cache key
     * @var string
     */
    private $cacheKey;

    /**
     * Cache directory
     * @var string
     */
    private $cacheDir;

    /**
     * Cache initialization
     * @param string $cacheDir Cache folder
     * @param array $cacheParams Additional options
     */
    public function __construct($cacheDir, $cacheParams = [])
    {
        if (isset($cacheParams['expireTimeSeconds'])) {
            $this->expireTimeSeconds = $cacheParams['expireTimeSeconds'];
        }

        if (isset($cacheParams['key'])) {
            $this->cacheKey = $cacheParams['key'];
        }

        $this->cacheDir = $cacheDir;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir);
        }
    }

    /**
     * Check if cache is valid
     * @param string|boolean $cacheKey
     * @return boolean
     */
    public function isValid($cacheKey = false)
    {
        $cacheFile = $this->getCacheFile($cacheKey);
        if (file_exists($cacheFile)) {
            if (time() - filemtime($cacheFile) <= $this->expireTimeSeconds) return true;
        }
        return false;
    }

    /**
     * Save data to the cache
     * @param mixed $data
     * @param string|boolean $cacheKey
     * @throws \Exception If cache dir is not writable
     */
    public function set($data, $cacheKey = false)
    {
        if (!is_writable($this->cacheDir)) {
            throw new \Exception('Cache dir is not writable');
        }
        file_put_contents($this->getCacheFile($cacheKey), serialize($data));
    }

    /**
     * Get data from the cache
     * @param string|boolean $cacheKey
     * @return mixed|bool Actual cache content or false if it is not valid
     */
    public function get($cacheKey = false)
    {
        if ($this->isValid($cacheKey)) {
            return unserialize(file_get_contents($this->getCacheFile($cacheKey)));
        }
        return false;
    }

    /**
     * Get name of cache file by a given cache key
     * @param string|boolean $cacheKey
     * @return string
     * @throws \Exception If cache key is not available
     */
    private function getCacheFile($cacheKey)
    {
        if (empty($cacheKey)) {
            // cache key was possibly passed within constructor options
            $cacheKey = $this->cacheKey;
        }

        if (empty($cacheKey)) {
            throw new \Exception('Cache key is not set');
        }

        return $this->cacheDir . DS . $cacheKey;
    }
}