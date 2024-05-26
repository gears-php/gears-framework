<?php

namespace Gears\Framework\View;

use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;

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
        $this->path = str_replace('/', DS, dirname($filePath));
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
                throw new \RuntimeException('Template file not found: ' . $filePath);
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
        return $this->path . DS . $this->name;
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
     * Setting template variable(s)
     */
    public function assign(array|string $mixed, mixed $value = null): static
    {
        if (is_array($mixed)) {
            foreach ($mixed as $name => $value) {
                $this->assign($name, $value);
            }
        } else {
            $this->vars[$mixed] = $value;
        }
        return $this;
    }

    /**
     * Render template
     * @param array $vars Template variables
     * @return string Rendered template content
     */
    public function render(array $vars = []): string
    {
        $processed = $this->process($this->vars + $vars);

        // we have decorator parent template
        if ($this->parent()) {
            return $this->parent()->setBlocks($this->blocks)->render();
        } else {
            return $processed;
        }
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
     */
    protected function tBlock(array $args): void
    {
        $this->blocksOpened[] = $args['name'];
        ob_start();
    }

    /**
     * Close template block
     */
    protected function tEndblock(): void
    {
        $currentBlock = array_pop($this->blocksOpened);

        if (!$currentBlock) {
            throw new \RuntimeException('&lt;/block&gt; used without opening counterpart');
        }

        $block_content = ob_get_clean();

        if (!isset($this->blocks[$currentBlock])) {
            $this->blocks[$currentBlock] = $block_content;
        }

        echo $this->blocks[$currentBlock];
    }

    protected function tExtension(array $args): void
    {
        echo $this->view->extension($args['name']);
    }

    /**
     * Include another template into current template
     */
    protected function tInclude(array $args): void
    {
        echo $this->view->load($args['name'])->assign($this->vars)->render();
    }

    /**
     * Extend template with current one
     */
    protected function tExtends(array $args): void
    {
        $this->parent($this->view->load($args['name']));
    }

    /**
     * Invoke a specific partial template per each variables set inside collection
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
                $tpl->assign($vars);
                $html .= $tpl->render();
            }

            return $html;
        } elseif (isset($args['alt'])) {
            return $args['alt'];
        }

        return '';
    }

    /**
     * Process and return template content
     */
    private function process(array $vars): string
    {
        try {
            ob_start();
            extract($vars);
            eval('?>' . $this->content);
            return ob_get_clean();
        } catch (InvalidCharacter|\Exception $e) {
            $this->exception($e);
        }
    }

    /**
     * Encode the given php variable into its valid js representation
     * @deprecated
     */
    public function jsEncode(mixed $var): float|string
    {
        if (is_numeric($var) || is_bool($var)) {
            // prepare numeric
            return (float)($var);
        } elseif (is_string($var)) {
            // prepare string
            return '"' . preg_replace("/\r?\n/", "\\n", addslashes($var)) . '"';
        } elseif (is_array($var)) {
            // prepare array
            return json_encode($var);
        }

        return var_export($var, true);
    }

    /**
     * Process the given exception by adding more info
     *
     * @throw \RuntimeException More detailed template exception
     *
     * @param \Exception $e
     */
    private function exception(\Exception $e)
    {
        throw new \RuntimeException(sprintf('%s template rendering error', $this->getFilePath()), 0, $e);
    }
}
