<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c) 2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\Cache;

/**
 * Cache interface which should be implemented by any specific cache system
 * @package    Gears\Framework
 * @subpackage Cache
 */
interface CacheInterface
{
    public function __construct($cachePath, $cacheParams = []);

    /** Check if cache is valid. */
    public function isValid($cacheKey = false);

    public function set($data, $cacheKey = false);

    public function get($cacheKey = false);

    public function getTime($cacheKey = false);
}
