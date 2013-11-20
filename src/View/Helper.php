<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework\View;

use Gears\Framework\View\View;

/**
 * Abstract helper
 * @todo        Remove this class
 * @package    Gears\Framework
 * @subpackage View
 * @abstract
 */
abstract class Helper
{
    /**
     * View instance holder
     *
     * @var View
     */
    private $_view = null;

    /**
     * Storage for generated html
     *
     * @var string
     */
    protected $_html = '';

    /**
     * Helper constructor. Requires {@link View} class instance to be passed
     */
    public function __construct(View $view)
    {
        $this->_view = $view;
        $this->init();
    }

    /**
     * Compulsory helper functionality should be placed here
     */
    public function init()
    {
    }

    /**
     * The entry point for any helper class
     * @param array|null $params
     * @return Helper class instance
     */
    public function __invoke($params = null)
    {
        return $this;
    }

    /**
     * Get all stored html
     *
     * @return string
     */
    public function get()
    {
        return $this->_html;
    }

    /**
     * @return View
     */
    public function view()
    {
        return $this->_view;
    }
}