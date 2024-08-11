<?php

/**
 * @author denis.krasilnikov@gears.com
 */
declare(strict_types=1);

namespace Gears\Framework\Application;

use Gears\Db\ActiveRecord\ActiveManager;
use Gears\Db\Adapter\AdapterAbstract;
use Gears\Framework\View\View;

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

    public function getDb(): AdapterAbstract
    {
        /** @var AdapterAbstract $db */
        $db = $this->get('db');

        return $db;
    }

    public function getActiveRecord(): ActiveManager
    {
        /** @var ActiveManager $arm */
        $arm = $this->get('arm');

        return $arm;
    }

    public function getView(): View
    {
        /** @var View $view */
        $view = $this->get('view');

        return $view;
    }
}
