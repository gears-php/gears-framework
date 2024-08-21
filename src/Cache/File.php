<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c) 2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\Cache;

/**
 * File system cache implementation
 * @package    Gears\Framework
 * @subpackage Cache
 */
class File implements CacheInterface
{
    /**
     * Cache expiration time in seconds. Setting to 0 meaning infinite time. Default is 900 (15 minutes).
     */
    private int $expiresIn;

    /**
     * Cache key
     */
    private string $cacheKey;

    /**
     * Extension of cache files
     */
    private string $cacheFileExtension;

    /**
     * Cache directory
     */
    private string $cacheDir;

    /**
     * Cache initialization
     * @param string $cacheDir Cache folder
     * @param array $cacheParams Additional options
     * @throws \Exception If cache dir is not writable
     */
    public function __construct($cacheDir, $cacheParams = [])
    {
        $this->cacheDir = $cacheDir;
        $this->expiresIn = intval($cacheParams['expires_in'] ?? 900);
        $this->cacheFileExtension = $cacheParams['file_extension'] ?? '';
        $this->cacheKey = $cacheParams['key'] ?? '';

        try {
            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir);
            }
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Unable to create cache directory "%s"', $this->cacheDir), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     * Missing cache file or expired one - both result to false.
     * @throws \Exception
     */
    public function isValid($cacheKey = ''): bool
    {
        $cacheFile = $this->getCacheFile($cacheKey);
        if (file_exists($cacheFile)) {
            if (0 == $this->expiresIn) {
                return true;
            }
            if (time() - filemtime($cacheFile) <= $this->expiresIn) {
                return true;
            }
        }
        return false;
    }

    /**
     * Save data to the cache
     * @throws \Exception If cache dir is not writable
     */
    public function set(mixed $data, $cacheKey = '')
    {
        if (!is_writable($this->cacheDir)) {
            throw new \Exception('Cache dir is not writable');
        }
        // store cache content
        file_put_contents($this->getCacheFile($cacheKey), serialize($data));
    }

    /**
     * Get data from the cache file or return NULL if it is not valid.
     * @throws \Exception
     */
    public function get($cacheKey = ''): mixed
    {
        if ($this->isValid($cacheKey)) {
            return unserialize(file_get_contents($this->getCacheFile($cacheKey)));
        }

        return null;
    }

    /**
     * Get UNIX time when the specific cache was last modified
     * @throws \Exception
     */
    public function getTime($cacheKey = ''): int
    {
        if (file_exists($cacheFile = $this->getCacheFile($cacheKey))) {
            return filemtime($cacheFile);
        }
        return -1;
    }

    /**
     * Get name of cache file by a given cache key
     * @throws \Exception If cache key is not available
     */
    private function getCacheFile(string $cacheKey): string
    {
        if (empty($cacheKey)) {
            // cache key was possibly passed within constructor options
            $cacheKey = $this->cacheKey;
        }

        if (empty($cacheKey)) {
            throw new \Exception('Cache key is not set');
        }

        return $this->cacheDir . DIRECTORY_SEPARATOR . $cacheKey . rtrim('.' . $this->cacheFileExtension, '.');
    }
}
