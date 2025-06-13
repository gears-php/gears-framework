<?php

namespace Gears\Framework\View;

/**
 * Template is an object representation of specific template file
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
    protected array $blocks = [];
    protected array $blocksOpened = [];

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
        if ($cache?->isValid($templateKey) && $cache->getTime($templateKey) > filemtime($filePath)) {
            $this->content = $cache->get($templateKey)['content'] ?: null;
        }

        if ($this->content) {
            return;
        }

        $this->content = implode('', (new Parser($this->nodeConverter()))->parseFile($filePath));

        $cache?->set([
            'file' => $filePath,
            'content' => $this->content
        ], $templateKey);
    }

    public function nodeConverter(): \Closure
    {
        return function (array $node, int $level) {
            if (isset($node['html'])) {
                return $node['html'];
            }
            return $level ? $node : sprintf('<?php %s ?>', var_export($node, true));
        };
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
    private function tBlock(array $args): void
    {
        if (!isset($args['name'])) {
            throw new RenderingException(
                sprintf(
                    'Missing &lt;block&gt; "name" attribute in %s:%d',
                    $this->getFilePath(),
                    $args['_tag_pos']
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
    private function tEndBlock(): void
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
    private function tExtension(array $args): void
    {
        echo $this->view->extension($args['name']);
    }

    private function tPage(array $args): string
    {
        return implode(array_keys($args, null));
    }

    private function tDate(array $args): void
    {
        ob_start();
        echo 'date[';
    }

    private function tEndDate(array $args): string
    {
        return ob_get_clean() . ']';
    }

    private function tIterate(array $args): string
    {
        return 'iterate[';
    }

    private function tEndIterate(array $args): string
    {
        return ']';
    }

    private function tRaw(array $args): void
    {
        ob_start();
        echo 'raw[';
    }

    private function tEndRaw(array $args): string
    {
        return ob_get_clean() . ']';
    }

    /**
     * Include another template into current template
     * @noinspection PhpUnused
     */
    private function tInclude(array $args): void
    {
        if (!$args['_void']) {
            ob_start();
            return;
        }
        echo $this->view->load($args['name'])->render(
            $this->vars + array_diff_key($args, array_flip(['_tag_pos', '_void', 'name']))
        );
    }

    private function tEndInclude(array $args): void
    {
        $templateName = trim(ob_get_clean());
        echo $this->view->load($templateName)->render($this->vars);
    }

    /**
     * Extend template with current one
     * @noinspection PhpUnused
     */
    private function tExtends(array $args): void
    {
        $this->parent($this->view->load($args['name']));
    }
}
