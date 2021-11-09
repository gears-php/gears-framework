<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c) 2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Helper;

use Gears\Framework\View\View;

/**
 * Abstract helper
 * @todo        Remove this class
 * @package    Gears\Framework
 * @subpackage View
 * @abstract
 */
abstract class HelperAbstract
{
    /**
     * View instance holder
     *
     * @var View
     */
    protected $view;

    /**
     * Storage for generated html
     *
     * @var string
     */
    protected $html = '';

    /**
     * Helper constructor. Requires {@link View} class instance to be passed
     */
    public function __construct(View $view)
    {
        $this->view = $view;
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
     * @return HelperAbstract class instance
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
        return $this->html;
    }

    /**
     * @return View
     */
    protected function view()
    {
        return $this->view;
    }
}
