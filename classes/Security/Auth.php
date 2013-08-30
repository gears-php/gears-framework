<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\Security;

use Gears\Framework\Session;

/**
 * Provides basic authentication functionality
 *
 * @package    Gears\Framework
 * @subpackage Security
 * @uses       Session
 */
class Auth
{
	/**
	 * Check if we have auth data in session, so auth was passed already
	 */
	public function passed()
	{
		return !is_null(Session::get('auth'));
	}

	/**
	 * Authentication attempt
	 * @param array $entities
	 * @return bool
	 */
	public function authenticate(array $entities)
	{
		if (1 == count($entities)) {
			Session::set('auth', $entities[0]);
			return true;
		} else return false;
	}

	public function get()
	{
		return Session::get('auth');
	}

	public function clear()
	{
		Session::delete('auth');
	}
}