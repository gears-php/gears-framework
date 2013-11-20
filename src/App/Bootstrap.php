<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 11/16/13
 * Time: 11:53 AM
 */

namespace Gears\Framework\App;

/**
 * Application bootstrapper
 * @package Gears\Framework\App
 */
class Bootstrap
{
    /**
     * @var App
     */
    protected $app = null;

    /**
     * Bootstrap need just an application
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Do all necessary initializations and run application
     * @param string $configFile
     */
    public function run($configFile = 'app')
    {
        // mandatory application basic initialization
        $this->app->init($configFile);

        // do all descendant initializations
        $methods = get_class_methods(get_called_class());
        foreach ($methods as $method) {
            if (0 === strpos($method, 'init')) {
                call_user_func([$this, $method]);
            }
        }

        $this->app->run();
    }

    /**
     * Get application
     * @return App
     */
    public function getApp()
    {
        return $this->app;
    }
}