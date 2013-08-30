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
     * Set HTTP response header(s)
     * @param string|array $name
     * @param $value|null
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
    }

    /**
     * Append response body content to an existing one
     * @param $body
     */
    public function appendBody($body) {
        $this->body .= $body;
    }

    /**
     * Set response body content
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
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
     */
    public function setJson($data)
    {
        $this->setBody(json_encode($data));
        $this->setHeader('Content-Type', 'application/json');
    }

    /**
     * Do immediate response by setting HTTP headers, status and body
     */
    public function flush()
    {
        foreach ($this->headers as $hName => $hValue) {
            header("$hName: $hValue");
        }
        echo $this->body;
    }
}