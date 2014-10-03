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
    protected $classInfo = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getConfigFile()
    {
        $fileExt = $this->app->getConfig()->getReader()->getFileExt();
        return  $this->getModuleDir() . '/config/module' . $fileExt;
    }

    public function register()
    {
        ClassLoader::register($this->getClassInfo()->getNamespaceName(), $this->getModuleDir() . '/src/');
        return $this;
    }

    public function load()
    {
    }

    protected function getModuleDir()
    {
        return dirname($this->getClassInfo()->getFileName());
    }

    protected function getClassInfo()
    {
        if (!$this->classInfo) {
            $this->classInfo = new ReflectionClass($this);
        }

        return $this->classInfo;
    }
}
