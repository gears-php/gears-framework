<?php
/**
 * @author deniskrasilnikov86@gmail.com
 */
namespace Gears\Framework\App;

/**
 * Provides functionality for services management
 * @package Gears\Framework\App
 */
trait ServiceProvider
{
    /**
     * Services container instance
     * @var Services
     */
    private $services = null;

    /**
     * Get application service
     * @param string $name
     * @return object
     */
    public function get()
    {
        return call_user_func_array([$this->services, 'get'], func_get_args());
    }

    /**
     * Set application service
     * @param string $name
     * @param callable $callable
     */
    public function set($name, $callable)
    {
        $this->services->set($name, $callable);
        return $this;
    }

    /**
     * Set a shared application service
     * @param string $name
     * @param callable $callable
     */
    public function setShared($name, $callable)
    {
        $this->services->setShared($name, $callable);
        return $this;
    }

    /**
     * Set fallback service
     * @param $callable
     */
    public function fallback($callable)
    {
        $this->services->fallback($callable);
        return $this;
    }
}