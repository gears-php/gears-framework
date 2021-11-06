<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */

declare(strict_types=1);

namespace Gears\Framework\View;

use Gears\Framework\Cache\CacheInterface;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

/**
 * View
 *
 * @package    Gears\Framework
 * @subpackage View
 */
class View
{
    /**
     * Stores all possible helper class namespaces
     * @var array
     */
    private $helperNamespaces = [];

    /**
     * Collection of all currently called helpers
     * @var array
     */
    private $helpers = [];

    /**
     * Stores paths where to search for template files
     * @var array
     */
    private $templatePaths = [];

    /**
     * Collection of all currently loaded templates
     * @var array
     */
    private $templates = [];

    /**
     * Template files extension
     * @var string
     */
    private $templateFileExt = '.phtml';

    /**
     * Cache implementation instance
     * @var CacheInterface
     */
    private $cache;

    /**
     * View extensions
     */
    private $extensions;

    public function __construct(array $options = [])
    {
        // setup template file path(s)
        if (isset($options['templates'])) {
            if (is_string($tpl = $options['templates'])) {
                $this->addTemplatePath($tpl);
            } elseif (is_array($tpl)) {
                $this->setTemplatePaths($tpl);
            }
        }

        // setup cache storage
        if (isset($options['cache']) && $options['cache'] instanceof CacheInterface) {
            $this->setCache($options['cache']);
        }

        $this->addHelperNamespace(__NAMESPACE__ . '\\Helper');
    }

    /**
     * Set cache storage
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get cache storage
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set absolute paths where to search templates
     * @param array $paths
     */
    public function setTemplatePaths($paths)
    {
        $this->templatePaths = $paths;
    }

    /**
     * Add a single templates directory path
     * @param string $path
     */
    public function addTemplatePath($path)
    {
        $this->templatePaths[] = realpath($path);

        return $this;
    }

    /**
     * Added helper classes namespace
     * @param string $namespace
     */
    public function addHelperNamespace($namespace)
    {
        $this->helperNamespaces[] = $namespace;
        return $this;
    }

    /**
     * Get template by full or relative name OR alias name (for already stored templates)
     *
     * @param string $name Template name to get the template. Extension is optional
     * @param null|string $alias (optional) Unique name under which to store and access template for future
     *
     * @throws \Exception
     *
     * @return Template
     */
    public function load($name, $alias = null)
    {
        // possibly we are accessing already stored template
        if (!is_string($alias) && isset($this->templates[$name])) {
            return $this->templates[$name];
        }

        // make sure file name includes extension since it is optional to be passed within $name
        $fileName = str_replace($this->templateFileExt, '', $name) . $this->templateFileExt;

        // if no template alias name given use file name
        $alias = $alias ?: $fileName;

        // whether template object is already stored under alias name
        if (isset($this->templates[$alias])) {
            return $this->templates[$alias];
        }
        // search for template file within template paths and store it using alias name
        foreach ($this->templatePaths as $filePath) {
            $filePath = $filePath . DS . $fileName;

            if (is_file($filePath)) {
                $tpl = new Template($filePath, $this);
                break;
            }
        }

        if (!isset($tpl)) {
            throw new \Exception('Template file not found: ' . $fileName);
        }

        return $this->templates[$alias] = $tpl;
    }

    /**
     * Render template and return its content.
     */
    public function render(string $template, array $vars = null): string
    {
        return $this->load($template)->assign($vars)->render();
    }

    /**
     * Call a helper with a given name and parameters.
     * @param string $helperName Helper name
     * @param array (optional) $params Helper parameters
     * @return string Helper execution result
     * @throws \Exception
     */
    public function helper($helperName, $params = [])
    {
        if (!isset($this->helpers[$helperName])) {
            // try to find helper within all registered namespaces and instantiate it
            foreach ($this->helperNamespaces as $namespace) {
                $className = $namespace . '\\' . ucfirst($helperName);

                if (class_exists($className)) {
                    $this->helpers[$helperName] = new $className($this);
                    break;
                }
            }

            if (!isset($this->helpers[$helperName])) {
                throw new \RuntimeException(sprintf('No helper class found for "%s" helper', $helperName));
            }
        }

        return call_user_func_array($this->helpers[$helperName], $params);
    }

    /**
     * @param $ext
     */
    public function addExtension($ext)
    {
        $this->extensions[] = $ext;
    }

    public function extension($name): string
    {
        foreach ($this->extensions as $extension) {
            if (method_exists($extension, $name)) {
                return (string)call_user_func([$extension, $name]);
            }
        }

        return '';
    }

    /**
     * Resolve refs like `AdminModule@content:index` to real templates inside modules, e.g. AdminModule/templates/content/index[.phtml]
     *
     * @todo Not used atm. Think if it should be dropped in favor of more explicit and clear mehanism without refs.
     */
    private function parseTemplateReference(string $reference): string
    {
        if (false === strpos($reference, '@')) {
            return $reference;
        }

        $chunks = explode('@', trim($reference, '@'));

        $templateName = SRC_PATH;

        if (count($chunks) == 2) {
            $templateName .= array_shift($chunks) . DS;
        }

        $templateName .= 'templates' . DS . array_shift($chunks);

        return $templateName;
    }
}
