<?php
/**
 * @package   Gf
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gf\Core\Exception;

/**
 * Resource not found exception. Thrown in case of nonexistent
 * controller class or method was requested
 * @package    Gf
 * @subpackage Core
 */
class ResourceNotFound extends \Exception
{
	public function __construct($message)
	{
		parent::__construct($message);
	}
}