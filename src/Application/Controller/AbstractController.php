<?php

/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
declare(strict_types=1);

namespace Gears\Framework\Application\Controller;

use Symfony\Component\HttpFoundation\Response;
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
     *
     * @param string $uri Resource uri
     * @param int $code HTTP response code
     *
     * @return Response
     */
    public function redirect(string $uri, int $code = Response::HTTP_FOUND): Response
    {
        return (new Response)
            ->setStatusCode($code)
            ->headers->set('Location', '/' . trim($uri, ' /') . '/');
    }
}
