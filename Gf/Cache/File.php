<?php
/**
 * @package   Gf
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gf\Cache;

/**
 * File system cache implementation
 *
 * @package    Gf
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
		$cacheFile = $this->_getCacheFile($cacheKey);
		if (file_exists($cacheFile)) {
			if (time() - filemtime($cacheFile) <= $this->expireTimeSeconds) return true;
		}
		return false;
	}

	/**
	 * Save data to the cache
	 * @param mixed $data
	 * @param string|boolean $cacheKey
	 */
	public function save($data, $cacheKey = false)
	{
		if (!is_writable($this->cacheDir)) {
			throw new \Exception('Cache dir is not writable');
		}
		file_put_contents($this->_getCacheFile($cacheKey), serialize($data));
	}

	/**
	 * Load cached data
	 * @param string|boolean $key
	 * @return mixed
	 */
	public function load($cacheKey = false)
	{
		if ($this->isValid($cacheKey)) {
			return unserialize(file_get_contents($this->_getCacheFile($cacheKey)));
		}
		return false;
	}

	/**
	 * Get name of cache file by a given cache key
	 * @param string|boolean $cacheKey
	 */
	private function _getCacheFile($cacheKey)
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