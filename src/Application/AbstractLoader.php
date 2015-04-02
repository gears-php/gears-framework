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
     * Return application directory
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
        $configFile = 'app' . rtrim('_' . $env, '_') . $fileExt;
        $config->load($this->getAppDir() . '/config/' . $configFile);
        return (new Application($config, new Services))->load();
    }
}
