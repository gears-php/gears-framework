<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\Application;

use Gears\Storage\Storage;
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
    use ServiceAware;

    /**
     * If to ignore error_reporting() level (so @ error suppression symbol
     * will take no effect and all errors will be still handled).
     * By default set to false in {@link handleErrors()} method
     */
    private $ignoreErrorReporting;

    /**
     * Configuration storage
     * @var Storage
     */
    private $config;

    /**
     * @param Storage $config Configuration storage
     * @param Services $services
     */
    public function __construct(Storage $config, Services $services)
    {
        $this->config = $config;
        $this->services = $services;
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
            $module = new $moduleClassName;
            $module->setServices($this->services);
            $this->config->merge($module->getConfigFile());
            $module->register()->load();
        }

        return $this;
    }

    /**
     * Handle the income request, dispatch it to specific controller action and return the response
     * @throws RouteNotFound
     */
    public function handle(Request $request)
    {
        $this->set('request', $request);
        $router = new Router;
        $router->addRoutingConfiguration($this->config['routing']);
        $route = $router->match($request->init());
        $controllerResolver = new ControllerResolver;

        if ($route && $controllerResolver->resolve($route)) {
            // before requested action is invoked event
            $this->dispatch('beforeAction');

            $controller = $controllerResolver->getController();
            $controller->setServices($this->services);
            /** @var Response $response */
            $response = call_user_func_array([$controller, $controllerResolver->getAction()], [
                $route->getParams()
            ]);

            if (!$response instanceof Response) {
                throw new \RuntimeException('Controller action must return response');
            }

            // after requested action is invoked event
            $this->dispatch('afterAction');

            // special event for rendering step
            $this->dispatch('render', [$response]);

            // before HTTP response event
            $this->dispatch('beforeResponse');

            $response->flush();

            // after response is done
            $this->dispatch('afterResponse');
        } else {
            throw new RouteNotFound($request->getHttpMethod() . ' ' . $request->getPathUri());
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
