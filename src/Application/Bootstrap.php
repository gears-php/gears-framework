<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 11/16/13
 * Time: 11:53 AM
 */

namespace Gears\Framework\Application;

/**
 * Application bootstrapper
 * @package Gears\Framework\Application
 */
class Bootstrap
{
    /**
     * @var Application
     */
    protected $app = null;

    /**
     * Bootstrap need just an application
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Do all necessary initializations and run application
     * @param string $configName
     */
    public function run($configName = 'app')
    {
        // mandatory application basic initialization
        $this->app->init($configName);

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
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }
}
