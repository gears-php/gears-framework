<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\App\Exception;

/**
 * Resource not found exception. Thrown in case of nonexistent
 * controller class or method was requested
 * @package    Gears\Framework
 * @subpackage App
 */
class ResourceNotFound extends \Exception
{
	public function __construct($message)
	{
		parent::__construct($message);
	}
}