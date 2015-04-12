<?php

namespace Gears\Framework\Application;

use Gears\Storage\Storage;

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
    abstract public function getAppDir();

    /**
     * Instantiate and load application including main services
     * @param string $env
     * @return Application
     */
    public function load($env = '')
    {
        // service container
        $services = new Services;
        $services->set('app.loader', $this);

        // configuration storage
        $config = new Storage;
        $fileExt = $config->getReader()->getFileExt();
        $configFile = 'app' . rtrim('_' . $env, '_') . $fileExt;
        $config->load($this->getAppDir() . '/config/' . $configFile);
        $services->set('config', $config);

        return (new Application($config, $services))->load();
    }
}
