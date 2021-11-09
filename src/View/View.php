<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c) 2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

declare(strict_types=1);

namespace Gears\Framework\View;

use Exception;
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
     */
    private array $helperNamespaces = [];

    /**
     * Collection of all currently called helpers
     */
    private array $helpers = [];

    /**
     * Stores paths where to search for template files
     */
    private array $templatePaths = [];

    /**
     * Collection of all currently loaded templates
     */
    private array $templates = [];

    /**
     * Template files extension
     */
    private string $templateFileExt = '.phtml';

    /**
     * Cache implementation instance
     */
    private CacheInterface $cache;

    /**
     * View extensions
     */
    private array $extensions;

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
     */
    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * Get cache storage
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Set absolute paths where to search templates
     */
    public function setTemplatePaths($paths): void
    {
        $this->templatePaths = $paths;
    }

    /**
     * Add a single templates directory path
     */
    public function addTemplatePath($path): static
    {
        $this->templatePaths[] = realpath($path);

        return $this;
    }

    /**
     * Added helper classes namespace
     */
    public function addHelperNamespace(string $namespace): static
    {
        $this->helperNamespaces[] = $namespace;

        return $this;
    }

    /**
     * Get template by full or relative name OR alias name (for already stored templates)
     *
     * @param string $name Template name to get the template. Extension is optional
     * @param string|null $alias (optional) Unique name under which to store and access template for future
     *
     * @throws Exception
     *
     */
    public function load(string $name, string $alias = null): Template
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
            throw new Exception('Template file not found: ' . $fileName);
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
     * @param array $params (optional) Helper parameters
     * @return mixed
     * @throws Exception
     */
    public function helper(string $helperName, array $params = []): mixed
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

    public function addExtension(ExtensionInterface $ext): void
    {
        $this->extensions[$ext->getName()] = $ext;
    }

    public function extension($name): string
    {
        if (!isset($this->extensions[$name])) {
            throw new \RuntimeException("View extension `$name` is not registered");
        }

        return $this->extensions[$name]();
    }

    /**
     * Resolve refs like `AdminModule@content:index` to real templates inside modules, e.g. AdminModule/templates/content/index[.phtml]
     *
     * @deprecated todo Not used atm. Think if it should be dropped in favor of more explicit and clear mechanism without refs.
     */
    private function parseTemplateReference(string $reference): string
    {
        if (!str_contains($reference, '@')) {
            return $reference;
        }

        $chunks = explode('@', trim($reference, '@'));

        $templateName = 'SRC_PATH';

        if (count($chunks) == 2) {
            $templateName .= array_shift($chunks) . DS;
        }

        $templateName .= 'templates' . DS . array_shift($chunks);

        return $templateName;
    }
}
