<?php

/**
 * @copyright For the full copyright and license information, please view the LICENSE files included in this source code.
 */
declare(strict_types=1);

namespace Gears\Framework\Application;

use ErrorException;
use Gears\Db\ActiveRecord\ActiveManager;
use Gears\Db\Db;
use Gears\Framework\Debug;
use Gears\Storage\Reader\Exception\FileNotFound;
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

    /** @var AbstractServiceSetup[] */
    protected array $setupClasses = [];

    public function __construct(protected Storage $config, protected Services $services)
    {
    }

    /**
     * Setup all application services
     */
    public function setup(string $env = ''): self
    {
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);

        $fileExt = $this->config->getReader()->getFileExt();
        $configFile = 'app' . rtrim('_' . $env, '_') . $fileExt;

        if (!is_file($configFile)) {
            $configFile = 'app' . $fileExt;
        }

        try {
            $this->config->load($this->getConfigDir() . "/$configFile");
        } catch (FileNotFound $e) {
            $this->handleException($e);
        }

        $this->setupServices($this->config);
        $this->setupDb();
        $this->setupActiveRecord();

        if (php_sapi_name() === 'cli') {
            return $this;
        }

        // below we build routes and do other stuff related to web-context only

        $this->set('router', $router = new Router);
        $this->config->merge($this->getConfigDir() . '/routing.yaml');

        // todo make config nodes validations
        $this->config['routes'] && $router->build($this->config['routes']);

        if (!$apiConfig = $this->config['api']) {
            return $this;
        }

        foreach ($apiConfig['resources']->raw() ?? [] as $resourceDefinition) {
            $router->buildResourceRoutes(
                $resourceDefinition['class'],
                $resourceDefinition['endpoint'],
                $apiConfig['handler'],
                $apiConfig['prefix']
            );
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

        $controllerResolver = (new ControllerResolver($this->config['controllers_namespace_prefix']))->resolve($route);

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
            $response = new JsonResponse($response);
        }

        $this->dispatch('app.response', [$response]);

        $response->send();

        $this->dispatch('app.done');
    }

    public function getConfigDir(): string
    {
        return $this->getAppDir() . '/config';
    }

    /**
     * Return application root directory
     */
    abstract public function getAppDir(): string;

    /**
     * Exception handler function. Trying to display detailed exception info
     */
    public function handleException(Throwable $e)
    {
        $statusCode = $e instanceof HttpExceptionInterface ? $e->getCode() : 500;

        if (!Debug::enabled()) {
            http_response_code($statusCode);
            echo "<h1>Oops! An Error Occurred</h1><h2>The server returned $statusCode code</h2>";

            return;
        }

        /** @var Request $request */
        $request = $this->has('request') ? $this->get('request') : false;

        if ($request && ($request->isXmlHttpRequest() || str_contains($request->getContentTypeFormat() . '', 'json'))) {
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
            ob_start();
            require_once __DIR__ . '/templates/exception.html.php';
            $content = ob_get_clean();
        }

        (new Response($content, $statusCode))->send();
    }

    /**
     * Error handler function
     *
     * @throws ErrorException
     */
    public function handleError(int $code, string $message, string $file, int $line): bool
    {
        if (error_reporting()) {
            throw new ErrorException($message, $code, 1, $file, $line);
        }

        return true;
    }

    /** Setup services from special classes */
    private function setupServices(Storage $config)
    {
        foreach ($this->setupClasses as $setupClass) {
            (new $setupClass())->setServices($this->services)->setup($config);
        }
    }

    /** Setup Database service */
    private function setupDb()
    {
        if ($this->has('db')) {
            return;
        }

        $dbCfg = $this->config->get('db');

        if (is_object($dbCfg) && $dbCfg->raw()) {
            $this->set('db', Db::connect($dbCfg->raw()));
        }
    }


    /** Setup Active Record service */
    private function setupActiveRecord()
    {
        if ($this->has('arm')) {
            return;
        }

        if ($this->config->get('active_record') !== false) {
            $this->set('arm', $arm = new ActiveManager($this->getDb()));
            $arm->setMetadataDirs([$this->getConfigDir() . '/active_record']);
        }
    }
}
