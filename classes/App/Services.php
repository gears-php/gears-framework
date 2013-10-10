<?php
namespace Gears\Framework\App;

/**
 * Simple DIC implementation for various application services
 * @package Gears\Framework\App
 */
class Services
{
    protected $services = [];
    protected $fallback = null;

    /**
     * Set fallback service which is called in case some specific
     * service was not found during {@see get()} call
     * @param $callable
     */
    public function call($callable)
    {
        if (is_callable($callable)) {
            $this->fallback = $callable;
        }
    }

    /**
     * Set service object instance or factory function which will return a new service object instance
     * @param string $name
     * @param callable|object $service
     */
    public function set($name, $service)
    {
        if (is_object($service) || is_callable($service)) {
            $this->services[$name] = $service;
        }
    }

    /**
     * Same as {@see set()} but will create only a single service instance and return
     * it for any future service calls
     * @param $name
     * @param callable $callable
     */
    public function setShared($name, $callable)
    {
        if (is_callable($callable)) {
            $this->set($name, function ($sc) use ($callable) {
                static $object;
                if (null === $object) {
                    $object = $callable($sc);
                }
                return $object;
            });
        }
    }

    /**
     * Get service object instance
     * @param string $name
     * @return object
     * @throws \Exception
     */
    public function get($name)
    {
        // return existing service
        if (isset($this->services[$name])) {
            if (is_callable($this->services[$name])) {
                $args = func_get_args();
                $args[0] = $this;
                return call_user_func_array($this->services[$name], $args);
            } else {
                return $this->services[$name];
            }
        }

        // return service from fallback
        if ($this->fallback) {
            $args[] = $this; // container
            $args[] = $name; // missed service
            $args[] = array_slice(func_get_args(), 1); // arguments
            $service = call_user_func_array($this->fallback, $args);
            if (is_object($service)) {
                return $service;
            }
        }

        throw new \Exception(sprintf('"%s" service does not exist', $name));
    }
}