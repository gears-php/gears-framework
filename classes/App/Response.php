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
     * Set HTTP response header(s)
     * @param string|array $name
     * @param $value|null
     * @return $this
     */
    public function setHeader($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $header => $value) {
                $this->setHeader($header, $value);
            }
        } elseif (is_string($name)) {
            $this->headers[$name] = $value;
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
        foreach ($this->headers as $hName => $hValue) {
            header("$hName: $hValue");
        }
        echo $this->body;
    }
}
