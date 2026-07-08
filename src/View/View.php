<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c) 2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

declare(strict_types=1);

namespace Gears\Framework\View;

use Gears\Framework\Cache\CacheInterface;
use Gears\Framework\View\Exception\EngineException;
use Gears\Framework\View\Exception\TemplateFileNotFoundException;

/**
 * View
 *
 * @package    Gears\Framework
 * @subpackage View
 */
class View
{
    /**
     * Collection of all currently loaded templates
     */
    private array $templates = [];

    /**
     * Template files extension
     */
    private string $templateFileExt = '.html';

    /** Custom functions */
    private array $functions = [];

    /** All supported template tags */
    private array $tags = [];

    /** @var array List of templates directories */
    private array $templatePaths = [];

    public function __construct(
        string|array $templatesDir,
        private readonly ?CacheInterface $cache = null,
        private readonly string $locale = '',
        private readonly bool $debugMode = false
    ) {
        // setup template file path(s)
        if (is_string($templatesDir)) {
            $this->addTemplatePath($templatesDir);
        } else {
            $this->setTemplatePaths($templatesDir);
        }
    }

    public function loadTags(string $tagsDir): static
    {
        // add external tag handlers
        foreach (glob($tagsDir . '/*.php') as $file) {
            $tagName = basename($file, '.php');
            $this->tags[$tagName] = include $file;
        }

        return $this;
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
     * Get template instance by its name.
     *
     * @param string $name Template name. File extension is optional
     */
    public function load(string $name): Template
    {
        // possibly we are accessing already stored template
        if (isset($this->templates[$name])) {
            return $this->templates[$name];
        }

        // make sure file name includes extension since it is optional to be passed within $name
        $fileName = str_replace($this->templateFileExt, '', $name) . $this->templateFileExt;

        foreach ($this->templatePaths as $filePath) {
            $filePath = $filePath . DIRECTORY_SEPARATOR . $fileName;

            if (is_file($filePath)) {
                $this->templates[$name] = $tpl = new Template($this, $this->tags);
                $tpl->compile($filePath);
                break;
            }
        }

        if (!isset($tpl)) {
            throw new TemplateFileNotFoundException($fileName);
        }

        return $this->templates[$name] = $tpl;
    }

    /**
     * Render template and return its content.
     */
    public function render(string $template, array $vars = null): string
    {
        return $this->load($template)->render($vars);
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

        throw new EngineException("Function \"$name\" is not found");
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }
}
