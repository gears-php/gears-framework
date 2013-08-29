<?php
/**
 * @package   Gf
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gf\Cache;

/**
 * Cache interface to be implemented by any cache system implementation
 *
 * @package    Gf
 * @subpackage Cache
 */
interface ICache
{
	function __construct($cachePath, $cacheParams = []);
	function isValid($cacheKey = false);
	function save($data, $cacheKey = false);
	function load($cacheKey = false);
}