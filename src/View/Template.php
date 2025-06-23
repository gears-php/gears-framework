<?php

namespace Gears\Framework\View;

use Gears\Framework\View\Tag\AbstractTag;

/**
 * Template is an object representation of specific template file
 *
 * @package    Gears\Framework
 * @subpackage View
 */
final class Template
{
    private string $name = '';
    private string|array $path = '';
    private array $nodes;
    private array $vars = [];
    private ?self $parent = null;
    private array $blocks = [];

    /** @var array<string, AbstractTag> All registered tags as <name, object> */
    private array $tags = [];

    /**
     * Process template file
     */
    public function __construct(protected View $view)
    {
    }

    /**
     * Compile template file
     * @param string $filePath Full path to a template file
     * @throws \RuntimeException If template file not found
     */
    public function compile(string $filePath, array $tags): void
    {
        if (!is_file($filePath)) {
            throw new TemplateFileNotFoundException($filePath);
        }

        $this->path = str_replace('/', DIRECTORY_SEPARATOR, dirname($filePath));
        $this->name = basename($filePath);
        $templateKey = md5($filePath);

        // tags creation should go before cache
        foreach ($tags as $tagClass) {
            /** @var AbstractTag $t */
            $t = new $tagClass($this);
            $this->tags[$t->getName()] = $t;
        }

        // try to use non-outdated compiled template from cache
        $cache = $this->view->getCache();
        if ($cache?->isValid($templateKey) && $cache->getTime($templateKey) > filemtime($filePath)) {
            $this->nodes = $cache->get($templateKey)['nodes'] ?: null;
        }

        if (isset($this->nodes)) {
            return;
        }

        $this->nodes = (new Parser(array_keys($this->tags)))->parseFile($filePath);
        $cache?->set([
            'file' => $filePath,
            'nodes' => $this->nodes
        ], $templateKey);
    }

    /**
     * Set template name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get template name
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setVar(string $name, mixed $value): void
    {
        $this->vars[$name] = $value;
    }

    public function getVar(string $name): mixed
    {
        return $this->vars[$name] ?? null;
    }

    public function getView(): View
    {
        return $this->view;
    }

    /**
     * Get path to the template file including filename itself
     */
    public function getFilePath(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->name;
    }

    public function renderNode(array $node): void
    {
        if (isset($node['html'])) {
            echo $node['html'];
            return;
        }

        $tagName = $node['tag'];
        if (!isset($this->tags[$tagName])) {
            throw new \RuntimeException("Unknown template tag: $tagName");
        }

        $this->tags[$tagName]->processNode($node);
    }

    /**
     * Render template
     * @param array $vars Template variables
     * @return string Rendered template content
     */
    public function render(array $vars = []): string
    {
        $this->vars = $vars;

        try {
            ob_start();
            foreach ($this->nodes as $node) {
                $this->renderNode($node);
            }
            $processed = ob_get_clean();
        } catch (\Throwable $e) {
            $bufferCount = ob_get_level();
            while ($bufferCount--) {
                ob_end_clean();
            }

            throw new RenderingException(sprintf('Template rendering error in %s', $this->getFilePath()), 0, $e);
        }

        if ($this->getParent()) {
            return $this->getParent()->render();
        } else {
            return $processed;
        }
    }

    /** Call view extension function */
    public function __call(string $name, array $args): mixed
    {
        return $this->view->callFunction($name, $args);
    }

    /**
     * Template inheritance. Is achieved together with blocks functionality.
     */
    public function extends(string $name): void
    {
        $this->parent = $this->view->load($name);
    }

    /**
     * Get parent template we extend
     */
    public function getParent(): ?Template
    {
        return $this->parent;
    }

    public function setBlockContent(string $blockName, string $content): void
    {
        $this->blocks[$blockName] = $content;
    }

    public function getBlockContent(string $blockName): ?string
    {
        return $this->blocks[$blockName] ?? null;
    }
}
