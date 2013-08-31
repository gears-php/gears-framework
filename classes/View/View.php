<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\View;

use Gears\Framework\View\Template;

/**
 * View
 *
 * @package    Gears\Framework
 * @subpackage View
 */
class View
{
    /**
     * Stores paths where to search for template files
     * @var string
     */
    protected $templatePaths = [];

    /**
     * Collection of all currently loaded templates
     * @var array
     */
    protected $templates = [];

    /**
     * Collection of all currently called helpers
     * @var array
     */
    protected $helpers = [];

    /**
     * Template files extension
     * @var string
     */
    protected $templateFileExt = '.phtml';

    /**
     * Path to compiled templates directory
     * @var string
     */
    protected $cachePath = '';

    /**
     * Constructor
     */
    public function __construct($paths = [])
    {
        $this->setTemplatePaths($paths);
    }

    /**
     * Magic method used to catch non-existant view method which is
     * treated as some helper class call
     * @param $method
     * @param $args
     * @return string
     */
    public function __call($method, $args)
    {
        return $this->helper($method, $args);
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
     * Add a single template path
     * @param string $path
     */
    public function addTemplatePath($path)
    {
        $this->templatePaths[] = $path;
    }

    /**
     * Set templates cache directory
     * @param string $cachePath
     */
    public function setCachePath($cachePath)
    {
        $this->cachePath = $cachePath;
    }

    /**
     * Return cached templates directory
     * @return string
     */
    public function getCachePath()
    {
        return $this->cachePath;
    }

    /**
     * Get template by full or relative name OR alias name (for already stored templates)
     * @param string $name Template name to get the template. Extension is optional
     * @param bool|string $alias (optional) Unique name under which to store and access template for future
     * @throws \Exception
     * @return Template
     */
    public function load($name, $alias = false)
    {
        // possibly we are accessing already stored template
        if (!is_string($alias) && isset($this->templates[$name])) {
            return $this->templates[$name];
        }

        // make sure file name includes extension since it is optional to be passed within $name
        $fileName = str_replace($this->templateFileExt, '', $name) . $this->templateFileExt;

        // if no template alias name given use file name
        $alias = $alias ? : $fileName;

        // whether template object is already stored under alias name
        if (!isset($this->templates[$alias])) {
            // search for template file within template paths and store it using alias name
            foreach ($this->templatePaths as $path) {
                $path = (0 === strpos($fileName, APP_PATH)) ? $fileName : $path . DS . $fileName;

                if (is_file($path)) {
                    $tpl = new Template($path, $this);
                    break;
                }
            }

            if (!isset($tpl)) {
                throw new \Exception('Template file not found: ' . $fileName);
            }

            $this->templates[$alias] = $tpl;
        }

        return $this->templates[$alias];
    }

    /**
     * Call a helper with a given name and parameters
     * @param string $name Helper name
     * @param array (optional) $params Helper parameters
     * @return string Helper execution result
     * @throws \Exception
     */
    public function helper($name, $params = [])
    {
        // calling some helper for the first time
        if (!isset($this->helpers[$name])) {
            $className = 'Gears\Framework\View\Helper\\' . ucfirst($name) . 'Helper';
            // store helper for future calls
            $this->helpers[$name] = new $className($this);
        }

        // finally return helper instance
        return call_user_func_array($this->helpers[$name], $params);
    }
}