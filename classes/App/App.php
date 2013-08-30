<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\App;

use Gears\Framework\Event\Dispatcher;
use Gears\Framework\App\Config\Config;
use Gears\Framework\App\Exception\ResourceNotFound;

/**
 * Provides the most low-level application functionality, controls the application flow
 *
 * @package    Gears\Framework
 * @subpackage App
 */
class App extends Dispatcher
{
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
     * Services container instance
     * @var Services
     */
    private $services = null;

    /**
     * If to ignore error_reporting() level (so @ error suppression symbol
     * will take no effect and all errors will be still handled).
     * By default set to false in {@link handleErrors()} method
     */
    private $ignoreErrorReporting;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->handleErrors();
        $this->handleExceptions();
        $this->services = new Services();
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
        echo sprintf('<pre>%s</pre>', $e.'');
        die();
    }

    /**
     * Error handler function. Generating exception with debug_backtrace info
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
     * Get application service
     * @param string $name
     * @return object
     */
    public function getService($name)
    {
        return $this->services->get($name);
    }

    /**
     * Set application service
     * @param string $name
     * @param callable $callable
     */
    public function setService($name, $callable)
    {
        $this->services->set($name, $callable);
    }

    /**
     * Set a shared application service
     * @param string $name
     * @param callable $callable
     */
    public function setSharedService($name, $callable)
    {
        $this->services->setShared($name, $callable);
    }

    /**
     * Dispatch request to the specific controller and action based on matched uri route pattern
     * @param string $uri
     * @throws ResourceNotFound
     */
    public function run($uri = null)
    {
        $uri = str_replace(rtrim(APP_URI, '/'), '', $uri ? : $_SERVER['REQUEST_URI']);

        // try to match route for the given uri
        if ($this->request = $this->resolve($uri, $this->getRoutesConfig())) {
            try {
				$this->response = new Response();

                // create controller reflection class
                $reflectionController = new \ReflectionClass($this->getControllerClassName());
                // instantiate controller class
                $controllerInstance = $reflectionController->newInstance($this);

                ob_start();

                // before requested action is invoked event
                $this->dispatch('beforeAction');

                // execute controller action with parameters
                $actionMethodName = lcfirst($this->toCamelCase($this->getRequest()->getActionName())) . 'Action';
                $actionResult = $reflectionController->getMethod($actionMethodName)
                    ->invokeArgs($controllerInstance, [$this->getRequest()->getParams()]);

                // after requested action is invoked event
                $this->dispatch('afterAction');

                // before HTTP response event                
                $this->dispatch('beforeResponse', [$actionResult]);

                $this->getResponse()->appendBody(ob_get_clean());
                $this->getResponse()->flush();

                // after response is done
                $this->dispatch('afterResponse');

            } catch (\ReflectionException $e) {
                // reflection exception means that nonexistent controller class or method
                // has been called so throwing an appropriate exception
                throw new ResourceNotFound($e->getMessage());
            }
        } else {
            throw new ResourceNotFound(sprintf('No route pattern found for "%s" uri', $uri));
        }
    }

    /**
     * Get full path to a specific base configuration file
     * @param string $fileName File name
     * @return string
     */
    private function getConfigFile($fileName)
    {
        $fileExt = Config::getReader()->getFileExt();
        return CONF_PATH . basename($fileName, $fileExt) . $fileExt;
    }

    /**
     * Get full paths to a specific modules configuration file
     * @param string $fileName File name
     * @return array
     */
    private function getModuleConfigFiles($fileName)
    {
        $fileExt = Config::getReader()->getFileExt();
        return glob(APP_PATH. 'modules' .DS. '*' .DS. 'config' .DS. basename($fileName, $fileExt) . $fileExt);
    }

    /**
     * Merge module routing files and basic routing file into a single sorted routes list
     * @return array
     */
    private function getRoutesConfig()
    {
        $routes = [];

        // load basic routes for root mvc
        foreach (Config::read($this->getConfigFile('routes')) as $route) {
            $routes[$route['route']] = $route + ['base' => ''];
        }

        // load module routes
        $moduleRouteFiles = $this->getModuleConfigFiles('routes');
        foreach ($moduleRouteFiles as $file) {
            $moduleMvcPath = str_replace(APP_PATH, '', dirname(dirname($file)));
            foreach (Config::read($file) as $route) {
                $routes[$route['route']] = $route + ['base' => $moduleMvcPath];
            }
        }

        // sort routes by keys to make more generic routes appear after more specific ones
        krsort($routes);
        return $routes;
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

    /**
     * Match the given url with one of route patterns and return Request instance holding all
     * the necessary request information. Return false if none of the routes was suitable 
     * @param string $uri Request uri
     * @param array $routes Routes configuration
     * @return Request instance
     * @throws \Exception
     */
    private function resolve($uri, $routes)
    {
        // per each route
        foreach ($routes as $route) {
            $route['params'] = [];

            // current route pattern used for matching
            $routePattern = $route['route'];

            // matching allowed request methods (if any)
            preg_match_all('/(\b(REST|GET|POST|PUT|PATCH|DELETE)\b)+/', $routePattern, $methods);
            if (count($methods[0])) {
                $routePattern = trim(str_replace(implode(' ', $methods[0]), '', $routePattern));
                $route['methods'] = $methods[0];
            }

            // replacing pattern placeholders with corresponding matching regexps
            $uriPattern = preg_replace($this->getPatterns(), $this->getMatchers(), $routePattern);

            // try to match uri string with current uri rule pattern
            // (query parameters are filtered out so not taken into account)
            if (preg_match('/^' . $uriPattern . '(?:\/)?(?:\?.*)?$/', $uri, $params)) {
                // request method limitation
                if (isset($route['methods']) && count($route['methods'])) {
                    // first check for REST pseudo method
                    if (in_array('REST', $route['methods'])) {
                        $route['to'] = rtrim($route['to'], '/') . '/' . strtolower($_SERVER['REQUEST_METHOD']);
                    // otherwise current request methods should be among allowed ones
                    } elseif (!in_array($_SERVER['REQUEST_METHOD'], $route['methods'])) {
                        throw new \Exception('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
                    }
                }

                array_shift($params);

                // we have placeholders values
                if (count($params)) {
                    $placeholders = [];
                    // find all possible placeholders inside the route rule
                    foreach ($this->getResolvers() as $resolvePattern => $fn) {
                        // match placeholders in the route
                        preg_match_all($resolvePattern, $routePattern, $matchedPlaceholders);
                        // remember matched placeholder names and their resolver functions
                        foreach ($matchedPlaceholders[1] as $placeholder) {
                            // remember placeholder by its position inside the route
                            $placeholders[strpos($routePattern, $placeholder)] = ['name' => $placeholder, 'fn' => $fn];
                        }
                    }

                    // build route parameters
                    if (count($placeholders) == count($params)) {
                        // sort placeholders according to matched param values
                        ksort($placeholders);
                        $placeholders = array_values($placeholders);
                        foreach ($placeholders as $k => $placeholder) {
                            $placeholder['fn']([$placeholder['name'] => $params[$k]], $route);
                        }
                    }
                }

                return new Request($route);
            } // uri was matched
        } // route

        return false;
    }

    /**
     * Regexps to match route pattern placeholders
     * @return array
     */
    private function getPatterns()
    {
        return [
            # /content/:id
            '/\/:\w+/',

            # /content*
            '/\*/',

            # url slashes
            '/\//'
        ];
    }

    /**
     * Regexps to be put instead of route pattern placeholder in order to match
     * real url values
     * @return array
     */
    private function getMatchers()
    {
        return [
            # /content/(3)
            '/([\w-]+)',

            # /content([/]anything[/]else[/]here)
            '(.*)',

            '\/'
        ];
    }

    /**
     * @return array
     */
    private function getResolvers()
    {
        return [
            # /:(id) => /id/3
            '/\/:(\w+)/' => function ($value, &$route) {
                $route['params'] += $value;
            },

            # /(*) => /anything/else/here
            '/(\*)/' => function ($value, &$route) {
                $route['to'] .= $value['*'];
            }
        ];
    }

   /**
     * Return full (including namespace) controller class name based
     * on current request controller name info
     * <code>
     * app\
     *   [mvc\path\]
     *     controllers\
     *       ControllerName
     * </code>
     * @return string
     */
    private function getControllerClassName()
    {
        $ns = '\\';
        return rtrim(
            // path to mvc folder
            str_replace(DS, $ns, str_replace(ROOT_PATH, '', $this->request->getMvcPath())), $ns
        ) . $ns .
        // next is "controllers" directory name part
        'controllers' . $ns . $this->toCamelCase($this->request->getControllerName()) . 'Controller';
    }

    /**
     * Convert a given string from `hyphen` format to camel case (e.g. this-is-name to ThisIsName)
     * @param string $str
     * @return string
     */
    private function toCamelCase($str)
    {
        return preg_replace(['/(\w+)/e', '/-/'], ['ucfirst("$1")', ''], $str);
    }    
}