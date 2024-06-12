<?php

declare(strict_types=1);

namespace Gears\Framework\Application;

use Gears\Framework\Application\Routing\Router;
use Gears\Storage\Reader\Exception\FileNotFound;
use Gears\Storage\Storage;
use ReflectionClass;

abstract class AbstractModule
{
    use ServiceAware;

    protected ?ReflectionClass $classInfo = null;

    /**
     * Register services and do other relevant preparations.
     */
    abstract public function registerServices(Storage $config): void;

    /**
     * Setup module based on its configuration.
     * @throws FileNotFound
     */
    public function load(): void
    {
        $config = (new Storage())->load($this->getModuleDir() . '/config/module.yaml');
        $this->registerServices($config);

        if (php_sapi_name() === 'cli') {
            return;
        }

        // todo make config reading in OOP way with nodes validations
        /** @var Router $router */
        $router = $this->get('router');
        $config['routing'] && $router->build($config['routing']);

        if (!$apiConfig = $config['api']) {
            return;
        }

        foreach ($apiConfig['resources']->raw() ?? [] as $resourceDefinition) {
            $router->buildResourceRoutes(
                $resourceDefinition['class'],
                $resourceDefinition['endpoint'],
                $apiConfig['handler'],
                $apiConfig['prefix']
            );
        }
    }

    /**
     * Get module base directory
     */
    protected function getModuleDir(): string
    {
        return dirname($this->getClassInfo()->getFileName());
    }

    /**
     * Return the module class info
     */
    protected function getClassInfo(): ReflectionClass
    {
        if (!$this->classInfo) {
            $this->classInfo = new ReflectionClass($this);
        }

        return $this->classInfo;
    }
}
