<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\Application;

use Gears\Config\Config;
use Gears\Framework\Event\Dispatcher;
use Gears\Framework\Application\Routing\Router;
use Gears\Framework\Application\Routing\Exception\RouteNotFound;
use Gears\Framework\Application\Controller\ControllerResolver;

/**
 * Provides the most low-level application functionality, controls the application flow
 * @package    Gears\Framework
 * @subpackage App
 */
class Application extends Dispatcher
{
    use ServiceProvider;

    /**
     * Request instance
     * @var Request
     */
    private $request = null;

    /**
     * Response instance
     * @var Response
     */
    private $response = null;

    /**
     * If to ignore error_reporting() level (so @ error suppression symbol
     * will take no effect and all errors will be still handled).
     * By default set to false in {@link handleErrors()} method
     */
    private $ignoreErrorReporting;

    /**
     * Config instance
     * @var Config
     */
    private $config = null;

    /**
     * @var Router
     */
    private $router = null;

    /**
     * @param Config $config
     * @param Services $services
     */
    public function __construct(Config $config, Services $services)
    {
        $this->config = $config;
        $this->services = $services;
    }

    /**
     * Return app config instance or config node value if node is given
     * @param string $node Dot-separated node to get the config value
     * @return mixed
     */
    public function getConfig($node = null)
    {
        if (null === $node) {
            return $this->config;
        }

        return $this->config->get($node);
    }

    /**
     * Get request instance
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get response instance
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Return services container
     * @return Services
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Setup main application services and load modules
     * @return $this
     */
    public function load()
    {
        $this->handleErrors();
        $this->handleExceptions();

        foreach ($this->config['modules'] as $moduleName) {
            $moduleClassName = $moduleName . '\\Module';
            /** @var AbstractModule $module */
            $module = new $moduleClassName($this);
            $this->config->merge($module->getConfigFile());
            $module->register()->load();
        }

        $this->router = new Router;

        if ($routing = $this->config['routing']) {
            $this->router->addRoutingConfiguration($routing);
        }

        return $this;
    }

    /**
     * Handle the income request, dispatch it to specific controller action and return the response
     * @throws RouteNotFound
     */
    public function handle(Request $request)
    {
        if ($route = $this->router->match($this->request = $request->init())) {
            $this->response = new Response;

            ob_start();

            // before requested action is invoked event
            $this->dispatch('beforeAction');

            // after requested action is invoked event
            $this->dispatch('afterAction');

            $controllerResolver = new ControllerResolver($this);
            $controller = $controllerResolver->getController($route);
            $controllerResult = call_user_func_array($controller, [$this->request->getParams()]);

            // special event for rendering step
            $this->dispatch('render', [$controllerResult]);

            // before HTTP response event
            $this->dispatch('beforeResponse');

            $this->getResponse()->appendBody(ob_get_clean());
            $this->getResponse()->flush();

            // after response is done
            $this->dispatch('afterResponse');

        } else {
            throw new RouteNotFound($this->request->getHttpMethod() . ' ' . $this->request->getPathUri());
        }
    }

    /**
     * Exception handler function. Trying to display detailed exception info
     * @param \Exception $e
     */
    public function exceptionHandler($e)
    {
        if (ob_get_length()) {
            ob_end_clean();
        }

        echo sprintf('<pre>%s</pre>', $e . '');
    }

    /**
     * Error handler function
     * @param int $code
     * @param string $message
     * @param string $file
     * @param int $line
     * @return bool
     * @throws \ErrorException
     */
    public function errorHandler($code, $message, $file, $line)
    {
        if (error_reporting() || $this->ignoreErrorReporting) {
            throw new \ErrorException($message, $code, 1, $file, $line);
        }

        return true;
    }

    /**
     * Setting custom exception handler
     * @return mixed set_exception_handler return value
     */
    private function handleExceptions()
    {
        return set_exception_handler([$this, 'exceptionHandler']);
    }

    /**
     * Setting custom error handler
     * @param bool $ignore
     * @return mixed set_error_handler return value
     */
    private function handleErrors($ignore = false)
    {
        $this->ignoreErrorReporting = $ignore;
        return set_error_handler([$this, 'errorHandler']);
    }
}
