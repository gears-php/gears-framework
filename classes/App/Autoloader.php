<?php

namespace Gears\Framework\App;

/**
 * Provides classes autoload functionality
 * @package    Gears\Framework
 * @subpackage App
 */
class Autoloader
{
    private $namespace;
    private $includePath;

    /**
     * Register a set of namespaces for classes autoloading
     * @param array $vendors
     */
    public static function registerNamespaces(array $vendors)
    {
        foreach ($vendors as $namespace => $includePath) {
            (new self($namespace, $includePath))->register();
        }
    }

    /**
     * @param string $namespace
     * @param string $includePath The base include path from within to load classes
     */
    public function __construct($namespace = null, $includePath = '')
    {
        $this->namespace = $namespace;
        $this->includePath = $includePath;
    }

    /**
     * Register spl_autoload() implementation
     * @return bool
     */
    public function register()
    {
        return spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Try to load the given class matching its namespace against the registered ones
     * @param $className
     */
    public function loadClass($className)
    {
        if (null === $this->namespace || 0 === strpos($className, $this->namespace)) {
            $fileName = str_replace($this->namespace, $this->includePath, str_replace(['_', '\\'], DS, $className)) . '.php';
            if (is_file($fileName)) {
                require_once $fileName;
            }
        }
    }
}