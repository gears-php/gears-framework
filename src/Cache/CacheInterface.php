<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
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
    public function isValid($cacheKey = false);
    public function set($data, $cacheKey = false);
    public function get($cacheKey = false);
    public function getTime($cacheKey = false);
}
