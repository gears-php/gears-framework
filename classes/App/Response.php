<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\App;

/**
 * Response
 * @package    Gears\Framework
 * @subpackage App
 */
class Response
{
    /**
     * HTTP response headers
     * @var array
     */
    private $headers = [];

    /**
     * HTTP response body
     * @var string
     */
    private $body = '';

    /**
     * Set HTTP response code
     * @param int $code
     * @return $this
     */
    public function setCode($code)
    {
        http_response_code($code);
        return $this;
    }

    /**
     * Set HTTP response header
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set several HTTP response headers at once
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * Set response body content
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }


    /**
     * Append response body content to an existing one
     * @param $body
     * @return $this
     */
    public function appendBody($body)
    {
        $this->body .= $body;
        return $this;
    }

    /**
     * Return response body content
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Encode the given data into JSON format
     * and prepare to respond json content
     * @param mixed $data
     * @return $this
     */
    public function setJson($data)
    {
        $this->setBody(json_encode($data))->setHeader('Content-Type', 'application/json');
        return $this;
    }

    /**
     * Do immediate response by setting HTTP headers and outputting body
     */
    public function flush()
    {
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->body;
    }
}
