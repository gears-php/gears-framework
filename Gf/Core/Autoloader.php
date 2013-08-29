<?php

namespace Gf\Core;

/**
 * Provides classes autoload functionality
 * @package    Gf
 * @subpackage Core
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
	 * Load class by a given class name
	 * @param $className
	 */
	public function loadClass($className)
	{
		if (null === $this->namespace || 0 === strpos($className, $this->namespace)) {
			$className = str_replace(['_', '\\'], DS, $className);
			$file = ($this->includePath ? rtrim($this->includePath, DS) . DS : '') . $className . '.php';
			if (is_file($file)) {
				require_once $file;
			}
		}
	}
}