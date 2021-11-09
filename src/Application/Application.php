<?php

/**
 * @copyright For the full copyright and license information, please view the LICENSE files included in this source code.
 */
declare(strict_types=1);

namespace Gears\Framework\Application;

use ErrorException;
use Gears\Framework\Debug;
use Gears\Storage\Storage;
use Gears\Framework\Event\Dispatcher;
use Gears\Framework\Application\Routing\Router;
use Gears\Framework\Application\Routing\Exception\RouteNotFound;
use Gears\Framework\Application\Controller\ActionNotFoundException;
use Gears\Framework\Application\Controller\ControllerResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

/**
 * Provides the most low-level "kernel" functionality, controls the application flow
 *
 * @package    Gears\Framework
 * @subpackage App
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
abstract class Application extends Dispatcher
{
    use ServiceAware;

    public function __construct(private Storage $config, protected Services $services)
    {
    }

    /**
     * Setup main application services and load modules
     */
    public function load(string $env = ''): self
    {
        $this->handleExceptions();
        $fileExt = $this->config->getReader()->getFileExt();
        $configFile = 'config' . rtrim('_' . $env, '_') . $fileExt;
        $this->config->load($this->getAppDir() . '/config/' . $configFile);
        $this->registerServices($this->config);

        if (php_sapi_name() !== 'cli') {
            $this->set('router', new Router);
        }

        foreach (glob($this->getAppDir() . '/src/*/Module.php') as $moduleFile) {
            /** @var AbstractModule $module */
            $module = require_once $moduleFile;
            $module->setServices($this->services);
            $module->load();
        }

        return $this;
    }

    /**
     * Handle income request, dispatch it to specific controller action and return the response
     *
     * @throws RouteNotFound|ActionNotFoundException
     */
    public function handle(Request $request)
    {
        $this->set('request', $request);

        if (!$route = $this->get('router')->match($request)) {
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
            array_merge($route->getResource() ? [$route->getResource()] : [], $route->getParams())
        );

        if (!$response instanceof Response) {
            $response = new JsonResponse(['data' => $response, '__time' => Debug::scriptTime(), '__memory' => Debug::getMemoryUsage()]);
        }

        $this->dispatch('app.response', [$response]);

        $response->send();

        $this->dispatch('app.done');
    }

    /**
     * Return application root directory
     */
    abstract public function getAppDir(): string;

    /**
     * Register all global level custom services
     */
    abstract public function registerServices(Storage $config);

    /**
     * Exception handler function. Trying to display detailed exception info
     */
    public function exceptionHandler(Throwable $e)
    {
        $statusCode = $e instanceof HttpExceptionInterface ? $e->getCode() : 500;

        if (!Debug::enabled()) {
            http_response_code($statusCode);
            echo "<h1>Oops! An Error Occurred</h1><h2>The server returned $statusCode code</h2>";

            return;
        }

        /** @var Request $request */
        $request = $this->get('request');

        if ($request->isXmlHttpRequest() || str_contains($request->getContentType() . '', 'json')) {
            $content = json_encode([
                                       'exception' => [
                                           'message' => $e->getMessage(),
                                           'code' => $e->getCode(),
                                           'file' => $e->getFile(),
                                           'line' => $e->getLine(),
                                           'trace' => $e->getTrace(),
                                       ],
                                   ]);
        } else {
            $content = "<pre>{$e}</pre>";
        }

        (new Response($content, $statusCode))->send();
    }

    /**
     * Error handler function
     *
     * @throws ErrorException
     */
    public function errorHandler(int $code, string $message, string $file, int $line): bool
    {
        if (error_reporting()) {
            throw new ErrorException($message, $code, 1, $file, $line);
        }

        return true;
    }

    /**
     * Setting custom exception handler
     *
     * @return callable|null set_exception_handler return value
     */
    private function handleExceptions(): ?callable
    {
        return set_exception_handler([$this, 'exceptionHandler']);
    }

    /**
     * Setting custom error handler
     */
    private function handleErrors(): ?callable
    {
        return set_error_handler([$this, 'errorHandler']);
    }
}
