<?php

namespace Gears\Framework\View;

use Gears\Framework\View\Exception\EngineException;
use Gears\Framework\View\Exception\RenderingException;
use Gears\Framework\View\Exception\TemplateFileNotFoundException;
use IntlDateFormatter;

/**
 * Template is an object representation of specific template file
 *
 * @package    Gears\Framework
 * @subpackage View
 */
final class Template
{
    private string $name = '';
    private string $path = '';
    private array $nodes;
    private array $vars = [];
    private ?self $parent = null;
    private array $blocks = [];

    /** @var array<string, callable> All registered tags as rendering handlers */
    private array $tags;

    /**
     * Name of all internal system tags
     * @var string[]
     */
    private array $sysTags;

    /**
     * Process template file
     */
    public function __construct(protected View $view, array $tags)
    {
        $this->tags = [
            'block' => $this->block(...),
            'date' => $this->date(...),
            'extends' => $this->extends(...),
            'include' => $this->include(...),
            'raw' => $this->raw(...),
            'repeat' => $this->repeat(...),
        ];
        $this->sysTags = array_keys($this->tags);
        $this->tags = array_merge($tags, $this->tags);
    }

    /**
     * Compile template file into nodes tree
     * @param string $filePath Full path to a template file
     * @throws TemplateFileNotFoundException If template file not found
     */
    public function compile(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new TemplateFileNotFoundException($filePath);
        }

        $this->path = str_replace('/', DIRECTORY_SEPARATOR, dirname($filePath));
        $this->name = basename($filePath);
        $templateKey = md5($filePath);

        // try to use non-outdated compiled template from cache
        $cache = $this->view->getCache();
        if ($cache?->isValid($templateKey)) {
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

    /** Get all template variables */
    public function getVars(): array
    {
        return $this->vars;
    }

    public function getLocale(): string
    {
        return $this->view->getLocale();
    }

    public function isDebugMode(): bool
    {
        return $this->view->isDebugMode();
    }

    /**
     * Get path to the template file including filename itself
     */
    public function getFilePath(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->name;
    }

//    /** Call view extension function */
//    public function callFunction(string $name, array $args): mixed
//    {
//        return $this->view->callFunction($name, $args);
//    }

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
                echo $this->renderNode($node);
            }
            $processed = ob_get_clean();
        } catch (\Throwable $e) {
            $bufferCount = ob_get_level();
            while ($bufferCount--) {
                ob_end_clean();
            }
            throw new EngineException(sprintf('Template rendering error in %s', $this->getFilePath()), 0, $e);
        }

        if ($this->getParent()) {
            return $this->getParent()->render();
        } else {
            return $processed;
        }
    }

    public function renderChildNodes(array $node): string
    {
        $html = '';
        foreach ($node['child_nodes'] ?? [] as $child) {
            $html .= $this->renderNode($child);
        }
        return $html;
    }

    /** Render given AST node */
    private function renderNode(array $node): string
    {
        if (isset($node['html'])) {
            // plain html without azul tags
            return $node['html'];
        }

        $ctx = new TagContext($node, $this);
        $ctx->innerHTML = $this->renderChildNodes($node);
        ob_start();
        return call_user_func(
                $this->tags[$node['tag']],
                $ctx,
                in_array($node['tag'], $this->sysTags) ? $node : null
            ) . ob_get_clean();
    }

    private function block(TagContext $ctx): string
    {
        if (empty($ctx->attrs['name'])) {
            throw new RenderingException('Missing "name" attribute', $ctx);
        }

        $name = $ctx->attrs['name'];
        $this->getParent()?->setBlockContent($name, $ctx->innerHTML);
        return $this->getBlockContent($name) ?: $ctx->innerHTML;
    }

    private function formatDate(string $dtm, string $locale, string $dateFormat, string $timeFormat): string
    {
        $formats = [
            'none' => IntlDateFormatter::NONE,
            'short' => IntlDateFormatter::SHORT,
            'medium' => IntlDateFormatter::MEDIUM,
            'long' => IntlDateFormatter::LONG,
            'full' => IntlDateFormatter::FULL,
        ];

        $dfm = $formats[$dateFormat] ?? throw new EngineException(
            "Unknown date format '$dateFormat' for IntlDateFormatter"
        );
        $tfm = $formats[$timeFormat] ?? throw new EngineException(
            "Unknown time format '$dateFormat' for IntlDateFormatter"
        );
        $formatter = IntlDateFormatter::create($locale, $dfm, $tfm);

        return $formatter->format(strtotime($dtm)) ?: '';
    }

    private function date(TagContext $ctx): string
    {
        if (empty($ctx->innerHTML)) {
            throw new RenderingException('Inner HTML should not be empty', $ctx);
        }

        return $this->formatDate(
            trim($ctx->innerHTML),
            $ctx->attrs['locale'] ?? $this->view->getLocale(),
            $ctx->attrs['df'] ?? 'long',
            $ctx->attrs['tf'] ?? 'short',
        );
    }

    /** Template inheritance. It is achieved together with @see block() functionality. */
    private function extends(TagContext $ctx): void
    {
        if (empty($ctx->attrs['name'])) {
            throw new RenderingException('Missing "name" attribute', $ctx);
        }
        $this->parent = $this->view->load($ctx->attrs['name']);
    }

    private function include(TagContext $ctx): string
    {
        if ($ctx->isVoid) {
            if (empty($ctx->attrs['name'])) {
                throw new RenderingException('Missing "name" attribute', $ctx);
            }
            return $this->view->load($ctx->attrs['name'])->render($this->vars);
        }

        if (empty($ctx->innerHTML)) {
            throw new RenderingException('Inner HTML should not be empty', $ctx);
        }

        return $this->view->load($ctx->innerHTML)->render($this->vars);
    }

    private function raw(TagContext $ctx): string
    {
        if (empty($ctx->innerHTML)) {
            throw new RenderingException('Inner HTML should not be empty', $ctx);
        }
        return htmlspecialchars_decode($ctx->innerHTML);
    }

    private function repeat(TagContext $ctx, array $node): string
    {
        $sourceVar = key($ctx->attrs);
        $sourceCollection = $ctx->v($sourceVar);
        if (!is_iterable($sourceCollection)) {
            throw new RenderingException(
                sprintf('Template variable "%s" is not iterable', $sourceVar),
                $ctx
            );
        }
        $destVar = current($ctx->attrs);
        $backupValue = $this->vars[$destVar];
        $html = '';
        foreach ($sourceCollection as $value) {
            $this->vars[$destVar] = $value;
            $html .= $this->renderChildNodes($node);
        }
        $this->vars[$destVar] = $backupValue;
        return $html;
    }
}
