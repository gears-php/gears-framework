<?php
namespace Gears\Framework\View;

use Gears\Framework\Cache\ICache;
use Gears\Framework\View\Parser;
use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;
use Gears\Framework\View\View;

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
    protected $parent = null;
    protected $view = null;
    protected $blocks = [];
    protected $blocksOpened = [];
    protected $disabled = false;

    /**
     * Process template file
     * @param string $filePath Full path to a template file
     * @param View $view
     * @param ICache (optional) $cache Cache storage instance to be used for storing compiled templates
     * @throws \Exception If template file not found
     */
    public function __construct($filePath, View $view, ICache $cache = null)
    {
        $this->path = str_replace('/', DS, dirname($filePath));
        $this->name = basename($filePath);
        $this->view = $view;

        $templateKey = md5($filePath);

        // try to use non-outdated processed template from cache
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
                throw new \Exception('Template file not found: ' . $filePath);
            }
        }
    }

    /**
     * Nonexistent method call is treated as view helper method call
     */
    public function __call($method, $params)
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
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get template name
     * @return string
     */
    public function getName()
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
     * Get view instance
     * @deprecated
     * @return View
     * @todo Check method usage outside of template class and remove if not used
     */
    public function getView()
    {
        return $this->view;
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
    public function blocks(array $blocks)
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
    public function render($vars = [])
    {
        if (!$this->disabled) {
            $processed = $this->process($this->vars + $vars);
            // we have decorator parent template
            if ($this->parent()) {
                return $this->parent()->blocks($this->blocks)->render();
            } else {
                return $processed;
            }
        }
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
     */
    private function tBlock($args)
    {
        $this->blocksOpened[] = $args['name'];
        ob_start();
    }

    /**
     * Close template block
     */
    private function tEndblock()
    {
        $currentBlock = array_pop($this->blocksOpened);
        if (!$currentBlock) {
            throw new \Exception('&lt;/block&gt; used without opening counterpart');
        }
        $block_content = ob_get_clean();
        if (!isset($this->blocks[$currentBlock])) {
            $this->blocks[$currentBlock] = $block_content;
        }
        echo $this->blocks[$currentBlock];
    }

    /**
     * Include another template into current template
     * @param array $args
     */
    private function tInclude(array $args)
    {
        echo $this->view->load($args['name'])->assign($this->vars)->render();
    }

    /**
     * Extend template with current one
     * @param array $args
     */
    private function tExtends(array $args)
    {
        $this->parent($this->view->load($args['name']));
    }

    /**
     * Generate style link tag
     * @param array $args
     * @return string
     */
    private function tCss(array $args)
    {
        $args['href'] = $this->completeUrl($args['src']); // Style file web url
        unset($args['src']);
        $args += ['media' => 'screen']; // default media type
        return sprintf('<link rel="stylesheet" type="text/css"%s />', $this->getTagAttributesString($args));
    }

    /**
     * Generate js script src tag
     * @param array $args
     * @return string
     */
    private function tJs(array $args)
    {
        $args['src'] = $this->completeUrl($args['src']); // Script file web url
        return sprintf('<script type="text/javascript"%s></script>', $this->getTagAttributesString($args));
    }

    /**
     * Invoke a specific partial template per each variables set inside collection
     * @param array $args
     * @return string
     */
    private function tImage(array $args)
    {
        $args['src'] = $this->completeUrl($args['src']); // Image web url
        return sprintf('<img%s />', $this->getTagAttributesString($args));
    }

    /**
     * Invoke a specific partial template per each variables set inside collection
     * @param array $args
     * @return string
     */
    private function tRepeat($args)
    {
        // partial template variables collection
        $collection = $this->vars[ltrim($args['source'], '$')];
        if (!empty($collection)) {
            /** @var $tpl Template */
            $tpl = $this->view->load($args['name']);
            $html = '';
            $collection = array_values($collection);
            foreach ($collection as $index => &$vars) {
                $vars['_TPL_INDEX_'] = $index;
                $tpl->assign($vars);
                $html .= $tpl->render();
            }
            return $html;
        } else {
            return __CLASS__ . ' error: partial collection is empty';
        }
    }

    /**
     * Gather all given key=>value pairs into html tag attributes string (e.g. attr="value")
     * @param $attrs
     * @return string
     */
    private function getTagAttributesString($attrs)
    {
        $attrsString = '';
        foreach ($attrs as $name => $value) {
            $attrsString .= sprintf(' %s="%s"', $name, $value);
        }
        return $attrsString;
    }

    /**
     * Process and return template content
     * @param array $vars
     * @return string
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
    }

    /**
     * Return full url for a given path
     */
    private function url($path)
    {
        // probably we have asset (img/js/css) path given
        // find out this by extension
        $ext = substr($path, strrpos($path, '.') + 1);
        switch ($ext) {
            // style
            case 'css':
                return \APP_URI . 'css/' . $path;
            // script
            case 'js':
                return \APP_URI . 'js/' . $path;
            // image
            case 'png':
            case 'gif':
            case 'jpg':
            case 'ico':
                return \APP_URI . 'img/' . $path;
            // just some url
            default:
                return \APP_URI . $path;
        }
    }

    /**
     * Complete full url for the given path
     */
    private function completeUrl($path)
    {
        return (0 === strpos($path, \APP_URI)) ? $path : $this->url($path);
    }

    /**
     * Return script with basic js application constants under 'app' namespace
     * @return string
     */
    private function appJs()
    {
        return sprintf('<script type="text/javascript">var app = app || {};%s</script>',
            $this->jsVars(['uri' => APP_URI, 'img_uri' => APP_URI . 'img/'], 'app')
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
    private function jsVars($vars, $namespace = null, $exceptions = [])
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
            return floatval($var);
        } elseif (is_string($var)) {
            // prepare string
            return '"' . preg_replace("/\r?\n/", "\\n", addslashes($var)) . '"';
        } elseif (is_array($var)) {
            // prepare array
            return json_encode($var);
        } else {
            // if do something here?
        }
    }

    /**
     * Process the given exception by adding more info
     * @throw \Exception More detailed template exception
     */
    private function exception(\Exception $e)
    {
        throw new \Exception(sprintf('%s in %s template', $e->getMessage(), $this->getFilePath()));
    }
}