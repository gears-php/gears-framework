<?php

/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
declare(strict_types=1);

namespace Gears\Framework\Application;

use Gears\Framework\Application\Exception\ActionNotFoundException;
use Gears\Framework\Application\Routing\Route;
use Gears\Framework\Debug;
use Gears\Storage\Storage;
use Gears\Framework\Event\Dispatcher;
use Gears\Framework\Application\Routing\Router;
use Gears\Framework\Application\Routing\Exception\RouteNotFound;
use Gears\Framework\Application\Controller\ControllerResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    private bool $ignoreErrorReporting;

    /**
     * Configuration storage
     */
    private Storage $config;

    public function __construct(Storage $config, Services $services)
    {
        $this->config = $config;
        $this->services = $services;
    }

    /**
     * Setup main application services and load modules
     */
    public function load()
    {
        $this->handleErrors();
        $this->handleExceptions();

        foreach ($this->config['modules'] as $moduleName) {
            // todo auto-discover modules
            $moduleClassName = $moduleName . '\\Module';
            /** @var AbstractModule $module */
            $module = new $moduleClassName;
            $module->setServices($this->services);
            $this->config->merge($module->getConfigFile());
            $module->load();
        }

        return $this;
    }

    /**
     * Handle the income request, dispatch it to specific controller action and return the response
     *
     * @throws RouteNotFound
     */
    public function handle(Request $request)
    {
        $this->set('request', $request);
        $router = new Router;
        // todo make config reading in OOP way with nodes validations
        $router->build($this->config['routing']);
        $apiConfig = $this->config['api'];

        foreach ($apiConfig['resources'] as $resourceDefinition) {
            foreach (
                $router->buildResourceRoutes(
                    $resourceDefinition['class'],
                    $resourceDefinition['endpoint'],
                    $apiConfig['handler'],
                    $apiConfig['prefix']
                ) as $route
            ) {
                $route->setAttribute('resource', $resourceDefinition['class']);
            }
        }

        if (!$route = $router->match($request)) {
            throw new RouteNotFound($request->getMethod() . ' ' . $request->getPathInfo());
        }

        $controllerResolver = (new ControllerResolver)->resolve($route);

        $controller = $controllerResolver->getController();
        $controller->setServices($this->services);

        if (!method_exists($controller, $controllerResolver->getAction())) {
            throw new ActionNotFoundException($controllerResolver);
        }

        $response = call_user_func_array(
            [$controller, $controllerResolver->getAction()],
            [$route->getAttribute('resource')] + $route->getParams()
        );

        if (!$response instanceof Response) {
            $response = new JsonResponse(['data' => $response, '__time' => Debug::scriptTime(), '__memory' => Debug::getMemoryUsage()]);
        }

        $this->dispatch('app.response', [$response]);

        $response->send();

        $this->dispatch('app.done');
    }

    /**
     * Exception handler function. Trying to display detailed exception info
     */
    public function exceptionHandler(\Throwable $e)
    {
        if (ob_get_length()) {
            ob_end_clean();
        }

        echo sprintf('<pre>%s</pre>', $e . '');
    }

    /**
     * Error handler function
     *
     * @throws \ErrorException
     */
    public function errorHandler(int $code, string $message, string $file, int $line): bool
    {
        if (error_reporting() || $this->ignoreErrorReporting) {
            throw new \ErrorException($message, $code, 1, $file, $line);
        }

        return true;
    }

    /**
     * Setting custom exception handler
     *
     * @return mixed set_exception_handler return value
     */
    private function handleExceptions()
    {
        return set_exception_handler([$this, 'exceptionHandler']);
    }

    /**
     * Setting custom error handler
     */
    private function handleErrors(bool $ignore = false): mixed
    {
        $this->ignoreErrorReporting = $ignore;

        return set_error_handler([$this, 'errorHandler']);
    }
}
