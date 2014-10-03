<?php

namespace Gears\Framework\Application;

/**
 * Provides classes autoload functionality by implementing PSR-4 autoloading standard
 * @package    Gears\Framework
 * @subpackage App
 */
class ClassLoader
{
    /**
     * Autoload mappings
     * @var array
     */
    private static $mappings = [];

    /**
     * Whether the SPL loader was registered
     * @var bool
     */
    private static $registered = false;

    /**
     * Namespace separator shortcut
     * @var string
     */
    private static $ns = '\\';

    /**
     * Directory separator shortcut
     * @var string
     */
    private static $ds = DIRECTORY_SEPARATOR;

    /**
     * Register a set of namespace prefixes and their base directories
     * @param array $mappings
     */
    public static function registerMappings(array $mappings)
    {
        foreach ($mappings as $namespacePrefix => $includePath) {
            self::register($namespacePrefix, $includePath);
        }
    }

    /**
     * Register current loader
     * @return bool
     */
    public static function register($namespacePrefix, $includePath)
    {
        $normalizedNamespacePrefix = trim($namespacePrefix, self::$ns) . self::$ns;
        $normalizedIncludePath = rtrim($includePath, self::$ds) . self::$ds;
        self::$mappings[$normalizedNamespacePrefix] = $normalizedIncludePath;

        if (!self::$registered) {
            spl_autoload_register([self::class, 'loadClass']);
            self::$registered = true;
        }
    }

    /**
     * Try to load the given class by matching its full name against the registered
     * namespace prefixes and then transforming it into class file full path
     * @param string $className
     */
    public static function loadClass($className)
    {
        foreach (self::$mappings as $namespacePrefix => $includePath) {
            if (0 === strpos($className, $namespacePrefix)) {
                $relativeClassName = substr($className, strlen($namespacePrefix));
                $fileName = $includePath . str_replace(self::$ns, self::$ds, $relativeClassName) . '.php';

                if (is_file($fileName)) {
                    require_once $fileName;
                    break;
                }
            }
        }
    }
}
