<?php

namespace Gears\Framework\View;

/**
 * Template class instantiated per each specific template file
 *
 * @package    Gears\Framework
 * @subpackage View
 */
class Template
{
    protected string $name = '';
    protected string|array $path = '';
    protected string $content = '';
    protected array $vars = [];
    protected ?self $parent = null;
    protected View $view;
    protected array $blocks = [];
    protected array $blocksOpened = [];

    /**
     * Process template file
     * @param string $filePath Full path to a template file
     * @param View $view
     * @throws \RuntimeException If template file not found
     */
    public function __construct(string $filePath, View $view)
    {
        $this->path = str_replace('/', DIRECTORY_SEPARATOR, dirname($filePath));
        $this->name = basename($filePath);
        $this->view = $view;

        $templateKey = md5($filePath);

        // try to use non-outdated processed template from cache
        $cache = $view->getCache();
        if ($cache && $cache->getTime($templateKey) > filemtime($filePath)) {
            $this->content = $cache->get($templateKey);
        }

        if (!$this->content) {
            // processing (compiling) template file only once during construction since
            // it can be used in a loop for rendering some repeatable content
            if (is_file($filePath)) {
                $this->content = (new Parser())->parseFile($filePath);
                $cache?->set($this->content, $templateKey);
            } else {
                throw new ViewException('Template file not found: ' . $filePath);
            }
        }
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

    /**
     * Get path to the template file including filename itself
     */
    public function getFilePath(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->name;
    }

    /**
     * Set template blocks content
     */
    public function setBlocks(array $blocks): static
    {
        $this->blocks = $blocks + $this->blocks;
        return $this;
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
            eval('?>' . $this->content);
            $processed = ob_get_clean();

        } catch (\Throwable $e) {
            $bufferCount = ob_get_level();
            while ($bufferCount--) {
                ob_end_clean();
            }

            throw new RenderingException(sprintf('Template rendering error in %s', $this->getFilePath()), 0, $e);
        }

        // we have decorator parent template
        if ($this->parent()) {
            return $this->parent()->setBlocks($this->blocks)->render();
        } else {
            return $processed;
        }
    }

    /** Get template variable value */
    public function __get(string $name): mixed
    {
        return $this->vars[$name] ?? null;
    }

    /** Call view extension function */
    public function __call(string $name, array $args): mixed
    {
        return $this->view->callFunction($name, $args);
    }

    /**
     * Get or set decorator parent template
     */
    private function parent(Template $tpl = null): ?Template
    {
        return $tpl ? $this->parent = $tpl : $this->parent;
    }

    /**
     * Start template block
     * @noinspection PhpUnused
     */
    protected function tBlock(array $args): void
    {
        if (!isset($args['name'])) {
            throw new RenderingException(
                sprintf(
                    'Missing "name" attribute for &lt;block&gt; tag in %s:%d',
                    $this->getFilePath(),
                    $args['_meta']['tag_pos']
                )
            );
        }

        $this->blocksOpened[] = $args['name'];
        ob_start();
    }

    /**
     * Close template block
     * @noinspection PhpUnused
     */
    protected function tEndblock(): void
    {
        $currentBlock = array_pop($this->blocksOpened);

        if (!$currentBlock) {
            throw new RenderingException('&lt;/block&gt; used without opening counterpart');
        }

        $block_content = ob_get_clean();

        if (!isset($this->blocks[$currentBlock])) {
            $this->blocks[$currentBlock] = $block_content;
        }

        echo $this->blocks[$currentBlock];
    }

    /** @noinspection PhpUnused */
    protected function tExtension(array $args): void
    {
        echo $this->view->extension($args['name']);
    }

    /**
     * Include another template into current template
     * @noinspection PhpUnused
     */
    protected function tInclude(array $args): void
    {
        echo $this->view->load($args['name'])->render($this->vars);
    }

    /**
     * Extend template with current one
     * @noinspection PhpUnused
     */
    protected function tExtends(array $args): void
    {
        $this->parent($this->view->load($args['name']));
    }

    /**
     * Invoke a specific partial template per each variables set inside collection
     * @noinspection PhpUnused
     */
    protected function tRepeat(array $args): string
    {
        // partial template variables collection
        $collection = $this->vars[ltrim($args['source'], '$')];

        if (count($collection)) {
            $tpl = $this->view->load($args['name']);
            $html = '';

            foreach (array_values($collection) as $index => &$vars) {
                $vars['_TPL_INDEX_'] = $index;
                $html .= $tpl->render($vars);
            }

            return $html;
        } elseif (isset($args['alt'])) {
            return $args['alt'];
        }

        return '';
    }
}
