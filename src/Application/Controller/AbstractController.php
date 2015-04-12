<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\Application\Controller;

use Gears\Framework\Application\Response;
use Gears\Framework\Application\ServiceAware;

/**
 * Abstract controller
 * @package    Gears\Framework
 * @subpackage App
 */
abstract class AbstractController
{
    use ServiceAware;

    /**
     * Redirect to another url within application
     * @param string $uri Resource uri
     * @param int $code HTTP response code
     * @return Response
     */
    public function redirect($uri, $code = 302)
    {
        return (new Response)
            ->setCode($code)
            ->setHeader('Location', '/' . trim($uri, ' /') . '/')
            ->flush();
    }
}
