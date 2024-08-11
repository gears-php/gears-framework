<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\Application\Routing\Exception;

use Gears\Framework\Application\HttpExceptionInterface;

/**
 * Exception thrown in case no route can be matched against request uri
 * @package    Gears\Framework
 * @subpackage Routing
 */
class GenerateUrlException extends \RuntimeException implements HttpExceptionInterface
{
}
