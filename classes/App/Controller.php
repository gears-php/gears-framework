<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\App;

use Gears\Framework\App\Config;
use Gears\Framework\App\Response;

/**
 * Abstract controller
 * @package    Gears\Framework
 * @subpackage App
 */
abstract class Controller
{
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
     * Get some application service by its name
     * @param $name
     * @return object
     */
    public function getService($name)
    {
        return $this->getApp()->getService($name);
    }

    /**
     * Redirect to another url within application
     * @param string $url Redirection resource
     * @param int $code HTTP response code
     */
    public function redirect($url, $code = 302)
    {
        $this->getResponse()
            ->setCode($code)
            ->setHeader('Location', APP_URI . trim($url, ' /') . '/')
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