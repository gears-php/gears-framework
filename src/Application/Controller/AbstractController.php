<?php

/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
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
     */
    public function redirect(string $uri, int $code = Response::HTTP_FOUND): Response
    {
        $response = (new Response)->setStatusCode($code);
        $response->headers->set('Location', '/' . trim($uri, ' /') . '/');

        return $response;
    }

    /**
     * Render given view template into response object.
     */
    public function render(string $template, array $vars = []): Response
    {
        return new Response($this->get('view')->render($template, $vars));
    }
}
