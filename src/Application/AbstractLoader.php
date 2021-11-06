<?php

declare(strict_types=1);

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
        $config = new Storage;
        $fileExt = $config->getReader()->getFileExt();
        $configFile = 'config' . rtrim('_' . $env, '_') . $fileExt;
        $config->load($this->getAppDir() . '/config/' . $configFile);

        $services = new Services;
        $services->set('config', $config);

        return (new Application($config, $services))->load();
    }
}
