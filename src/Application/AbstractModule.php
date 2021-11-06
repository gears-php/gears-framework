<?php

namespace Gears\Framework\Application;

use ReflectionClass;

abstract class AbstractModule
{
    use ServiceAware;

    /**
     * @var ReflectionClass
     */
    protected $classInfo;

    /**
     * Get module configuration file
     * @return string
     */
    public function getConfigFile()
    {
        $fileExt = $this->get('config')->getReader()->getFileExt();

        return $this->getModuleDir() . '/config/module' . $fileExt;
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
     * Concrete module loading. Register your module specific services
     * and do other preparations in this method
     *
     * @return void
     */
    abstract public function load();

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
