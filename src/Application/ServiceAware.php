<?php

/**
 * @author denis.krasilnikov@gears.com
 */
declare(strict_types=1);

namespace Gears\Framework\Application;

use Gears\Db\ActiveRecord\ActiveManager;
use Gears\Db\Adapter\AdapterAbstract;
use Gears\Framework\Application\Routing\Router;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides functionality for services management and access.
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
     * Whether given service exists in container.
     */
    public function has(string $name): bool
    {
        return $this->services->has($name);
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

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public function getRequest(): Request
    {
        return $this->get('request');
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public function getRouter(): Router
    {
        return $this->get('router');
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public function getDb(): AdapterAbstract
    {
        return $this->get('db');
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public function getActiveRecord(): ActiveManager
    {
        return $this->get('arm');
    }
}
