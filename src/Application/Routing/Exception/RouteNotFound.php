<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\Application\Routing\Exception;

/**
 * Exception thrown in case no route can be matched against request uri
 * @package    Gears\Framework
 * @subpackage Routing
 */
class RouteNotFound extends \Exception
{
    public function __construct($requestUri)
    {
        parent::__construct(sprintf('No route found for "%s" request', $requestUri));
    }
}
