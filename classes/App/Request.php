<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\App;

/**
 * Request
 *
 * @package    Gears\Framework
 * @subpackage App
 */
class Request
{
    /**
     * Request route pattern
     * @var string
     */
    private $pattern = '';

    /**
     * Controller name
     * @var string
     */
    private $controllerName = '';

    /**
     * Action name
     * @var string
     */
    private $actionName = '';

    /**
     * Stores full path to the MVC folders of currently routed module.
     * Empty by default meaning that MVC resides directly under APP_PATH folder
     *
     * <code>
     * APP_PATH
     *   [mvc/path/]
     *     controllers/
     *     models/
     *     views/
     * </code>
     * @var string
     */
    private $mvcPath = '';

    /**
     * Request parameters
     * @var array
     */
    private $params = [];

    /**
     * Extracting controller, action and parameters from the given route information
     * @param array $route
     */
    public function __construct(array $route)
    {
        $route = (object) $route;
        // remember url matching pattern
        $this->pattern = $route->route;
        // construct full path to MVC folder
        $this->mvcPath = rtrim(APP_PATH . $route->base, DS) . DS;
        
        preg_match('/\/(?P<class>[\w-]+)?(?:\/(?P<method>[\w-]+))?(?P<params>(?:\/[\w-]+)*)/', $route->to, $uri);
        
        // requested controller name part
        $this->controllerName = $uri['class'] ? : 'index';
        // requested action name part
        $this->actionName = $uri['method'] ? : 'index';
        // remember url placeholder params
        $this->params = $route->params;
        
        // extract "/name/value" url params
        $params = explode('/', $uri['params']);
        
        array_shift($params);
        
        foreach (array_chunk($params, 2) as $pair) {
            @list($key, $value) = $pair;
            if (!isset($this->params[$key])) {
                $this->params[$key] = $value;
            }
        }
    }

    /**
     * Get full path to the current MVC folder
     * @return string
     */
    public function getMvcPath()
    {
        return $this->mvcPath;
    }

    /**
     * Get requested controller name
     * @return string
     */
    public function getControllerName()
    {
        return $this->controllerName;
    }

    /**
     * Get requested controller action name
     * @return string
     */
    public function getActionName()
    {
        return $this->actionName;
    }

    /**
     * Get requested 'controller/action' name pair
     * @return string
     */
    public function getFullActionName()
    {
        return $this->controllerName . '/' . $this->actionName;
    }

    /**
     * Get all request params parsed from uri
     * @return object
     */
    public function getParams()
    {
        return (object)$this->params;
    }

    /**
     * Whether current request is asynchronous
     */
    public function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
    }

    /**
     * Get json decoded request data
     * @return array
     * @throws \Exception
     */
    public function getJson()
    {
        $headers = \getallheaders();
        if (isset($headers['Content-Type']) && $headers['Content-Type'] == 'application/json') {
            $decoded = \json_decode(\file_get_contents('php://input'));
            if (json_last_error() != JSON_ERROR_NONE) {
                throw new \Exception('JSON decode error. Please check request data to be a valid JSON string');
            }
            return $decoded;
        }
        return [];
    }

    /**
     * Get unique hash key based on all current request info (controller, action, parameters, etc.)
     * @return string
     */
    public function getUniqueKey()
    {
        return md5(var_export($this, true) + var_export($_GET, true));
    }
}
