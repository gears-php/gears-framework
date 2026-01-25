<?php

/**
 * @copyright For the full copyright and license information, please view the LICENSE files included in this source code.
 */
declare(strict_types=1);

namespace Gears\Framework\Application {

    use ErrorException;
    use Gears\Db\ActiveRecord\ActiveManager;
    use Gears\Db\Db;
    use Gears\Framework\Debug;
    use Gears\Framework\Events\RequestEvent;
    use Gears\Framework\Events\ResponseEvent;
    use Gears\Storage\Reader\Exception\FileNotFound;
    use Gears\Storage\Storage;
    use Gears\Framework\Events\Dispatcher;
    use Gears\Framework\Application\Routing\Router;
    use Gears\Framework\Application\Routing\Exception\RouteNotFound;
    use Gears\Framework\Application\Controller\UndefinedActionException;
    use Gears\Framework\Application\Controller\ControllerResolver;
    use Psr\EventDispatcher\EventDispatcherInterface;
    use Symfony\Component\HttpFoundation\JsonResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Throwable;

    /**
     * Provides the most low-level "kernel" functionality, controls the application flow
     *
     * @package    Gears\Framework
     * @subpackage App
     * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
     */
    final class Application
    {
        private ?Request $request = null;

        public function __construct(
            private readonly Storage $config = new Storage(),
            private readonly ServiceContainer $services = new ServiceContainer(),
            private readonly EventDispatcherInterface $dispatcher = new Dispatcher(),
            private readonly Router $router = new Router(),
        ) {
        }

        /**
         * Setup all application services
         */
        public function setup(): self
        {
            _container($this->services);

            $env = $_SERVER['APP_ENV'] ?? 'prod';
            ($env == 'dev') && Debug::enable();

            set_exception_handler([$this, 'handleException']);
            set_error_handler([$this, 'handleError']);

            $fileExt = $this->config->getReader()->getFileExt();

            try {
                $this->config->load($this->getConfigDir() . "/app$fileExt");
                $envFile = $this->getConfigDir() . "/app$env$fileExt";
                is_file($envFile) && $this->config->merge($envFile);
            } catch (FileNotFound $e) {
                $this->handleException($e);
            }

            $this->services->set('config', $this->config);
            $this->services->set('events', $this->dispatcher);

            $this->setupServices();
            $this->setupDb();
            $this->setupActiveRecord();

            if (php_sapi_name() === 'cli') {
                return $this;
            }

            // below we build routes and do other stuff related to web-context only

            $this->services->set('router', $this->router);
            $this->config->merge($this->getConfigDir() . "/routing$fileExt");

            // todo make config nodes validations
            $this->config['routes'] && $this->router->build($this->config['routes']);

            if (!$apiConfig = $this->config['api']) {
                return $this;
            }

            foreach ($apiConfig['resources'] ?? [] as $resourceDefinition) {
                $this->router->buildResourceRoutes(
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
         * @throws RouteNotFound|UndefinedActionException
         */
        public function handle(Request $request): void
        {
            $this->services->set('request', $this->request = $request);

            if (!$route = $this->router->match($request)) {
                throw new RouteNotFound($request->getMethod() . ' ' . $request->getPathInfo());
            }

            $this->dispatcher->dispatch($requestEvent = new RequestEvent($request, $route));

            if ($requestEvent->getResponse()) {
                $requestEvent->getResponse()->send();
                return;
            }

            $controllerResolver = (new ControllerResolver($this->config['controllers_namespace_prefix']))->resolve(
                $route
            );
            $controller = new ($controllerResolver->getController())();

            if (!method_exists($controller, $action = $controllerResolver->getAction())) {
                throw new UndefinedActionException($controllerResolver, $route);
            }

            $args = array_merge($route->getResource() ? [$route->getResource()] : [], $route->getParams());
            $response = $controller->$action(...$args);

            if (!$response instanceof Response) {
                $response = new JsonResponse($response);
            }

            $this->dispatcher->dispatch(new ResponseEvent($response));
            $response->send();
        }

        public function getConfigDir(): string
        {
            return $this->getAppDir() . '/config';
        }

        /**
         * Return application root directory
         */
        public function getAppDir(): string
        {
            return realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/../');
        }

        /**
         * Exception handler function. Trying to display detailed exception info
         */
        public function handleException(Throwable $e): void
        {
            $statusCode = $e instanceof HttpExceptionInterface ? $e->getCode() : 500;

            if (!Debug::enabled()) {
                http_response_code($statusCode);
                echo "<h1>Oops! An Error Occurred</h1><h2>The server returned $statusCode code</h2>";

                return;
            }

            if ($this->request && ($this->request->isXmlHttpRequest() || str_contains(
                        $this->request->getContentTypeFormat() . '',
                        'json'
                    ))) {
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
        private function setupServices(): void
        {
            foreach (require_once $this->getAppDir() . '/src/setup.php' as $setupClass) {
                (new $setupClass($this->config, $this->services))->setup();
            }
        }

        /** Setup Database service */
        private function setupDb(): void
        {
            if ($this->services->has('db')) {
                return;
            }

            if ($dbCfg = $this->config['db']) {
                $this->services->set('db', Db::connect($dbCfg));
            }
        }

        /** Setup Active Record service */
        private function setupActiveRecord(): void
        {
            if ($this->services->has('arm')) {
                return;
            }

            if ($this->config['active_record'] !== false) {
                /** @var Db $db */
                $db = $this->services->get('db');
                $this->services->set('arm', $arm = new ActiveManager($db));
                $arm->setMetadataDirs([$this->getConfigDir() . '/active_record']);
            }
        }
    }
}

namespace Gears\Framework\Application\Helper {

    use Gears\Db\ActiveRecord\ActiveManager;
    use Gears\Db\ActiveRecord\ActiveQuery;
    use Gears\Db\ActiveRecord\ActiveRecord;
    use Gears\Db\Db;
    use Gears\Framework\Application\Routing\Router;
    use Symfony\Component\HttpFoundation\RedirectResponse;
    use Symfony\Component\HttpFoundation\Request;

    use Symfony\Component\HttpFoundation\Response;

    use function Gears\Framework\Application\_container;

    function Service(...$args): object
    {
        return _container()->get(...$args);
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    function Request(): Request
    {
        return _container()->get('request');
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    function Router(): Router
    {
        return _container()->get('router');
    }

    /**
     * Render given view template into response object.
     */
    function Render(string $template, array $vars = []): Response
    {
        return new Response(_container()->get('view')->render($template, $vars));
    }

    /**
     * Redirect to another url location
     */
    function Redirect(string $url, int $responseCode = Response::HTTP_FOUND): Response
    {
        return new RedirectResponse($url, $responseCode);
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    function Db(): Db
    {
        return _container()->get('db');
    }

    function Query(string $class): ActiveQuery
    {
        /** @var ActiveManager $manager */
        $manager = _container()->get('arm');
        return $manager->createQuery($class);
    }

    /**
     * @template T of ActiveRecord
     * @param class-string<T> $class
     * @param mixed $id
     * @return T
     */
    function Model(string $class, mixed $id = null): ActiveRecord
    {
        /** @var ActiveManager $manager */
        $manager = _container()->get('arm');

        if ($id !== null) {
            return $manager->find($class, $id);
        }

        return $manager->createRecord($class);
    }
}