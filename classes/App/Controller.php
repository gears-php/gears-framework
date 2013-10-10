<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\App;

/**
 * Abstract controller
 * @package    Gears\Framework
 * @subpackage App
 */
abstract class Controller
{
    use ServicesProvider;

    /**
     * {@see App} instance holder
     * @var object
     */
    private $app = null;

    /**
     * Constructor
     * @param Request $request
     * @param Response $response
     * @param Services $services
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->services = $app->getServices();
        $this->init();
    }

    /**
     * Get application instance
     * @return App
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Return request instance
     * @return Request
     */
    public function getRequest()
    {
        return $this->getApp()->getRequest();
    }

    /**
     * Return response instance
     * @return Response
     */
    public function getResponse()
    {
        return $this->getApp()->getResponse();
    }

    /**
     * Redirect to another url within application
     * @param string $uri Resource uri
     * @param int $code HTTP response code
     */
    public function redirect($uri, $code = 302)
    {
        $this->getResponse()
            ->setCode($code)
            ->setHeader('Location', BASE_URL . trim($uri, ' /') . '/')
            ->flush();
    }

    /**
     * Method to be extended by descendant action controllers
     * in case they require some initial preparations
     */
    public function init()
    {
    }
}
