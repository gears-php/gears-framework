<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\Application;

/**
 * Request
 * @package    Gears\Framework
 * @subpackage App
 */
class Request
{
    /**
     * Base uri
     * @var string
     */
    protected $baseUri = null;

    /**
     * Request uri part relative to the base uri
     * @var string
     */
    protected $pathUri = null;

    /**
     * Request parameters
     * @var array
     */
    protected $params = [];

    /**
     * Return the uri part relative to the base uri
     * @return string
     */
    public function getPathUri()
    {
        if (null === $this->pathUri) {
            $this->pathUri = str_replace($this->getBaseUri(), '', $_SERVER['REQUEST_URI']);
        }

        return $this->pathUri;
    }

    /**
     * Return base uri of the application request
     * @return string
     */
    public function getBaseUri()
    {
        if (null === $this->baseUri) {
            $this->baseUri = isset($_SERVER['HTACCESS']) ? '' : $_SERVER['SCRIPT_NAME'];
        }

        return $this->baseUri;
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
     * Return http method of current server request
     * @return string
     */
    public function getHttpMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
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
        $headers = getallheaders();

        if (isset($headers['Content-Type']) && $headers['Content-Type'] == 'application/json') {
            $decoded = json_decode(file_get_contents('php://input'));

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
