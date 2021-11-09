<?php

/**
 * @author denis.krasilnikov@gears.com
 */
declare(strict_types=1);

namespace Gears\Framework\Application;

/**
 * Provides functionality for services management
 *
 * @package Gears\Framework\Application
 */
trait ServiceAware
{
    /**
     * Services container instance
     */
    protected Services $services;

    /**
     * Get application service
     */
    public function get(...$args): object
    {
        return call_user_func_array([$this->services, 'get'], $args);
    }

    /**
     * Set application service
     */
    public function set(string $name, callable|object $callable): static
    {
        $this->services->set($name, $callable);

        return $this;
    }

    /**
     * Set a shared application service
     */
    public function shared(string $name, callable $callable): static
    {
        $this->services->shared($name, $callable);

        return $this;
    }

    /**
     * Set fallback service
     */
    public function fallback($callable): static
    {
        $this->services->fallback($callable);

        return $this;
    }

    /**
     * Set service container
     */
    public function setServices(Services $services): static
    {
        $this->services = $services;

        return $this;
    }
}
