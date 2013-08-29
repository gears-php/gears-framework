<?php
/**
 * @package   Gf
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gf\Core;

use Gf\Core\Config;
use Gf\Core\Response;

/**
 * Abstract controller
 * @package    Gf
 * @subpackage Core
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
	 * @param string $url
	 */
	public function redirect($url)
	{
        $this->getResponse()->setHeader('Location', APP_URI . trim($url, ' /') . '/');
		$this->getResponse()->flush();
	}

	/**
	 * Method to be extended by descendant action controllers
	 * in case they require some initial preparations
	 */
	public function init()
	{
	}
}