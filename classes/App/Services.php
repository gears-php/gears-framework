<?php
namespace Gears\Framework\App;

/**
 * Simple DIC implementation for various application services
 * @package Gears\Framework\App
 */
class Services
{
    protected $services = [];

    /**
     * Set function which will return a new service object instance
     * @param string $name
     * @param callable $callable
     */
    public function set($name, $callable)
    {
        $this->services[$name] = $callable;
    }

    /**
     * Same as {@see set()} but will create only a single service instance and return
     * it for any future service calls
     * @param $name
     * @param callable $callable
     */
    public function setShared($name, $callable)
    {
        $this->set($name, function ($sc) use ($callable) {
            static $object;
            if (null === $object) {
                $object = $callable($sc);
            }
            return $object;
        });
    }

    /**
     * Get service object instance
     * @param string $name
     * @return object
     * @throws \Exception
     */
    public function get($name)
    {
        if (isset($this->services[$name]) && is_callable($this->services[$name])) {
            return call_user_func_array($this->services[$name], [$this]);
        } else {
            throw new \Exception(sprintf('"%s" service is not callable or does not exist', $name));
        }
    }
}