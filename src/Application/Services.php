<?php

declare(strict_types=1);

namespace Gears\Framework\Application;

use Exception;

/**
 * Simple DIC implementation for various application services
 * @package Gears\Framework\Application
 */
class Services
{
    /**
     * Internal services storage
     * @var array
     */
    protected array $services = [];

    /**
     * Fallback service
     * @var callable
     */
    protected $fallback;

    /**
     * Set fallback service factory which is called in case some specific
     * service was not found during {@see get()} call
     * @param $callable
     */
    public function fallback($callable): void
    {
        if (is_callable($callable)) {
            $this->fallback = $callable;
        }
    }

    /**
     * Set service object instance or factory function which will return a new service object instance
     */
    public function set(string $name, callable|object $service): void
    {
        if (is_object($service) || is_callable($service)) {
            $this->services[$name] = $service;
        }
    }

    /**
     * Whether given service exists in container.
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * Same as {@see set()} but will create only a single service instance and return
     * it for any future service calls
     */
    public function shared(string $name, callable $callable): void
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
     * @throws Exception
     */
    public function get(string $name): object
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

        throw new Exception(sprintf('"%s" service does not exist', $name));
    }
}
