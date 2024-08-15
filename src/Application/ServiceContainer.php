<?php

declare(strict_types=1);

namespace Gears\Framework\Application;

use RuntimeException;

/** @internal This function is intended for use inside framework source code. DO NOT USE IT IN YOUR APP CODE */
function _container(ServiceContainer $container = null): ServiceContainer
{
    static $instance = null;

    if (!$instance) {
        $instance = $container ?: new ServiceContainer();
    }

    return $instance;
}

/**
 * Simple container implementation for various application services
 * @package Gears\Framework\Application
 */
class ServiceContainer
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
     */
    public function fallback(callable $callable): void
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
     * @throws RuntimeException
     */
    public function get(string $name, ...$args): object
    {
        // return existing service
        if (isset($this->services[$name])) {
            if (is_callable($this->services[$name])) {
                array_unshift($args, $this);
                return call_user_func($this->services[$name], ...$args);
            } else {
                return $this->services[$name];
            }
        }

        // return service from fallback
        if ($this->fallback) {
            array_unshift($args, $this, $name);
            $service = call_user_func($this->fallback, ...$args);
            if (is_object($service)) {
                return $service;
            }
        }

        throw new RuntimeException(sprintf('"%s" service does not exist', $name));
    }
}
