<?php

namespace Gears\Framework\Application;

use ReflectionClass;

class AbstractModule
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var ReflectionClass
     */
    protected $classInfo;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get module configuration file
     * @return string
     */
    public function getConfigFile()
    {
        $fileExt = $this->app->getConfig()->getReader()->getFileExt();
        return  $this->getModuleDir() . '/config/module' . $fileExt;
    }

    /**
     * Register all module class load mappings
     * @return $this
     */
    public function register()
    {
        ClassLoader::register($this->getClassInfo()->getNamespaceName(), $this->getModuleDir() . '/src/');
        return $this;
    }

    /**
     * Custom module loading code
     */
    public function load()
    {
    }

    /**
     * Get module base directory
     * @return string
     */
    protected function getModuleDir()
    {
        return dirname($this->getClassInfo()->getFileName());
    }

    /**
     * Return the module class info
     * @return ReflectionClass
     */
    protected function getClassInfo()
    {
        if (!$this->classInfo) {
            $this->classInfo = new ReflectionClass($this);
        }

        return $this->classInfo;
    }
}
