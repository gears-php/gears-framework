<?php

namespace Gears\Framework\App;

/**
 * Provides classes autoload functionality
 * @package    Gears\Framework
 * @subpackage App
 */
class Autoloader
{
    private $namespacePrefix;
    private $includePath;
    private static $ns = '\\';
    private static $ds = DIRECTORY_SEPARATOR;

    /**
     * Register a set of namespace prefixes and their base directories
     * @param array $vendors
     */
    public static function registerNamespaces(array $vendors)
    {
        foreach ($vendors as $namespacePrefix => $includePath) {
            (new self($namespacePrefix, $includePath))->register();
        }
    }

    /**
     * @param string $namespacePrefix
     * @param string $includePath The base include path from within to load classes
     */
    public function __construct($namespacePrefix, $includePath)
    {
        // store fully qualified namespace prefix
        $this->namespacePrefix = rtrim(self::$ns . ltrim($namespacePrefix, self::$ns), self::$ns) . self::$ns;
        // store normalized base include path
        $this->includePath = rtrim($includePath, self::$ds) . self::$ds;
    }

    /**
     * Register current loader
     * @return bool
     */
    public function register()
    {
        return spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Try to load the given class by matching its full name against the registered
     * namespace prefixes and then transforming it into class file full path
     * @param string $className
     */
    public function loadClass($className)
    {
        // getting fully qualified class name
        $className = self::$ns . ltrim($className, self::$ns);
        if (0 === strpos($className, $this->namespacePrefix)) {
            $relativeClassName = substr($className, strlen($this->namespacePrefix));
            $fileName = $this->includePath . str_replace(self::$ns, self::$ds, $relativeClassName) . '.php';
            if (is_file($fileName)) {
                require_once $fileName;
            }
        }
    }
}