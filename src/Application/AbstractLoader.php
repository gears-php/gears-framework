<?php

namespace Gears\Framework\Application;

use Gears\Config\Config;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

/**
 * Application loader
 * @package Gears\Framework\Application
 */
abstract class AbstractLoader
{
    /**
     * @return string
     */
    abstract protected function getAppDir();

    /**
     * Prepare application environment and return it
     * @param string $env
     * @return Application
     */
    public function load($env = '')
    {
        $config = new Config;
        $fileExt = $config->getReader()->getFileExt();
        $configFile = 'app' . rtrim('_'. $env, '_') . $fileExt;
        $config->load($this->getAppDir() . '/config/' . $configFile);
        $app = new Application($config, new Services);
        $app->load($this->getAppDir());
        return $app;
    }

    /**
     * Load and return main app configuration depending on environment
     * @return Config
     */
    public function loadAppConfig()
    {

    }
}
