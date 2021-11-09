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
    protected $name = '';
    protected $path = '';
    protected $content = '';
    protected $vars = [];
    protected $parent;
    protected $view;
    protected $blocks = [];
    protected $blocksOpened = [];
    protected $disabled = false;

    /**
     * Process template file
     * @param string $filePath Full path to a template file
     * @param View $view
     * @throws \RuntimeException If template file not found
     */
    public function __construct($filePath, View $view)
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
                if ($cache) {
                    $cache->set($this->content, $templateKey);
                }
            } else {
                throw new \RuntimeException('Template file not found: ' . $filePath);
            }
        }
    }

    /**
     * Nonexistent method call is treated as view helper method call
     *
     * @param string $method
     * @param array $params
     *
     * @return string
     */
    public function __call(string $method, array $params)
    {
        return $this->view->helper($method, $params);
    }

    /**
     * Set template name
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set path to the template file
     *
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * Get template name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get path to the template file (not including filename itself)
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get path to the template file including filename itself
     * @return string
     */
    public function getFilePath()
    {
        return $this->path . DS . $this->name;
    }

    /**
     * Disable template rendering
     */
    public function disable()
    {
        $this->disabled = true;
    }

    /*
     * Set template blocks content
     * @return Template
     */
    public function setBlocks(array $blocks)
    {
        $this->blocks = $blocks + $this->blocks;
        return $this;
    }

    /**
     * Setting template variable(s)
     * @param string|array $mixed
     * @param mixed $value
     * @return Template
     */
    public function assign($mixed, $value = null)
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
    public function render(array $vars = [])
    {
        if (!$this->disabled) {
            $processed = $this->process($this->vars + $vars);

            // we have decorator parent template
            if ($this->parent()) {
                return $this->parent()->setBlocks($this->blocks)->render();
            } else {
                return $processed;
            }
        }

        return '';
    }

    /**
     * Get or set decorator parent template
     * @param Template $tpl
     * @return Template|null
     */
    private function parent(Template $tpl = null)
    {
        return $tpl ? $this->parent = $tpl : $this->parent;
    }

    /**
     * Start template block
     *
     * @param array $args
     */
    protected function tBlock(array $args)
    {
        $this->blocksOpened[] = $args['name'];
        ob_start();
    }

    /**
     * Close template block
     */
    protected function tEndblock()
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

    protected function tExtension(array $args)
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
     * Generate style link tag
     */
    protected function tCss(array $args): string
    {
        $args['href'] = $this->url($args['src']); // Style file web url
        unset($args['src']);
        $args += ['media' => 'all']; // default media type
        return sprintf('<link rel="stylesheet" type="text/css"%s />', $this->getTagAttributesString($args));
    }

    /**
     * Generate js script src tag
     * @param array $args
     * @return string
     */
    protected function tJs(array $args)
    {
        $args['src'] = $this->url($args['src']); // Script file web url
        return sprintf('<script type="text/javascript"%s></script>', $this->getTagAttributesString($args));
    }

    /**
     * @param array $args
     *
     * @return string
     */
    protected function tImage(array $args)
    {
        $args['src'] = $this->url($args['src']); // Image web url
        return sprintf('<img%s />', $this->getTagAttributesString($args));
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
     * Gather all given key=>value pairs into html tag attributes string (e.g. attr="value")
     * @param $attributes
     * @return string
     */
    private function getTagAttributesString($attributes)
    {
        $attributesString = '';
        foreach ($attributes as $name => $value) {
            $attributesString .= sprintf(' %s="%s"', $name, $value);
        }
        return $attributesString;
    }

    /**
     * Process and return template content
     * @param array $vars
     * @return string
     * @throws InvalidCharacter
     */
    private function process(array $vars)
    {
        try {
            ob_start();
            extract($vars);
            eval('?>' . $this->content);
            return ob_get_clean();
        } catch (InvalidCharacter $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->exception($e);
        }

        return '';
    }

    /**
     * Return full url for a given path
     *
     * @param string $path
     *
     * @return string
     */
    private function url(string $path): string
    {
        if (0 === strpos($path, '/')) {
            return $path;
        }

        $path = ltrim($path, '/');
        // probably we have asset (img/js/css) path given
        // find out this by extension
        $ext = substr($path, strrpos($path, '.') + 1);
        switch ($ext) {
            // style
            case 'css':
                return '/css/' . $path;
            // script
            case 'js':
                return '/js/' . $path;
            // image
            case 'png':
            case 'gif':
            case 'jpg':
            case 'ico':
                return '/img/' . $path;
            // just some url
            default:
                return $path;
        }
    }

    /**
     * Return script with basic js application constants under 'app' namespace
     *
     * @return string
     */
    private function appJs()
    {
        # todo move to app level template (or block)
        return sprintf('<script type="text/javascript">var app = app || {};%s</script>',
            $this->jsVars(['uri' => '', 'img_uri' => '/img'], 'app')
        );
    }

    /**
     * Pass multiple php variables to js
     * @param array $vars Variables array in (name => value) format
     * @param string $namespace (optional) Namespace, inside which create the variables
     * @param array $exceptions (optional) Array of names of some variables passed with first parameter which shouldn't be processed
     * @return string
     * @throws \Exception
     */
    private function jsVars($vars, $namespace = null, array $exceptions = [])
    {
        $script = '';

        foreach ($vars as $name => $value) {
            if (!in_array($name, $exceptions)) {
                $script .= sprintf('%s%s = %s;', $namespace . '.', $name, $this->jsEncode($value));
            }
        }

        return $script;
    }

    /**
     * Encode the given php variable into its valid js representation
     * @param mixed $var
     * @return string
     */
    private function jsEncode($var)
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
