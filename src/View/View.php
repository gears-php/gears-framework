<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c) 2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

declare(strict_types=1);

namespace Gears\Framework\View;

use Gears\Framework\Cache\CacheInterface;
use Gears\Framework\View\Extension\ExtensionInterface;
use Gears\Framework\View\Tag\Block;
use Gears\Framework\View\Tag\Date;
use Gears\Framework\View\Tag\Extend;
use Gears\Framework\View\Tag\Extension;
use Gears\Framework\View\Tag\IncludeTag;
use Gears\Framework\View\Tag\Iterate;
use Gears\Framework\View\Tag\Raw;
use RuntimeException;

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
     */
    private array $templatePaths = [];

    /**
     * Collection of all currently loaded templates
     */
    private array $templates = [];

    /**
     * Template files extension
     */
    private string $templateFileExt = '.html';

    /**
     * Cache implementation instance
     */
    private ?CacheInterface $cache = null;

    /**
     * View extensions
     */
    private array $extensions;

    /** Custom functions */
    private array $functions = [];

    /** All supported template tags */
    private array $tags = [
        Block::class,
        Date::class,
        Extend::class,
        Extension::class,
        IncludeTag::class,
        Iterate::class,
        Raw::class,
    ];

    /** "Global" variables. Are passed for all templates */
    public array $vars = [];

    public function init(
        mixed $templates = null,
        array $extensions = [],
        CacheInterface $cache = null,
        $tags = [],
    ): static {
        // setup template file path(s)
        if (!empty($templates)) {
            if (is_string($tpl = $templates)) {
                $this->addTemplatePath($tpl);
            } elseif (is_array($tpl)) {
                $this->setTemplatePaths($tpl);
            }
        }

        // setup cache storage
        if (isset($cache)) {
            $this->setCache($cache);
        }

        if ($extensions) {
            foreach ($extensions as $extension) {
                $this->addExtension($extension);
            }
        }

        array_push($this->tags, ...$tags);

        return $this;
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
    public function getCache(): ?CacheInterface
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
     * Get template by full or relative name OR alias name (for already stored templates)
     *
     * @param string $name Template name to get the template. Extension is optional
     * @param string|null $alias (optional) Unique name under which to store and access template for future
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
            $filePath = $filePath . DIRECTORY_SEPARATOR . $fileName;

            if (is_file($filePath)) {
                $this->templates[$alias] = $tpl = new Template($this);
                $tpl->compile($filePath, $this->tags);
                break;
            }
        }

        if (!isset($tpl)) {
            throw new TemplateFileNotFoundException($fileName);
        }

        return $this->templates[$alias] = $tpl;
    }

    /**
     * Render template and return its content.
     */
    public function render(string $template, array $vars = null): string
    {
        return $this->load($template)->render($this->vars + $vars);
    }

    public function addExtension(ExtensionInterface $ext): void
    {
        $this->extensions[$ext->getName()] = $ext;
    }

    public function extension($name): string
    {
        if (!isset($this->extensions[$name])) {
            throw new RuntimeException("View extension `$name` is not registered");
        }

        return $this->extensions[$name]();
    }

    public function addFunction(string $name, callable $func): static
    {
        $this->functions[$name] = $func;

        return $this;
    }

    public function callFunction(string $name, array $args): mixed
    {
        if (isset($this->functions[$name])) {
            return $this->functions[$name](...$args);
        }

        throw new ViewException("Function \"$name\" is not found");
    }
}
