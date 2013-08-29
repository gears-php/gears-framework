<?php
/**
 * Shortcut for native DIRECTORY_SEPARATOR constant
 */
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

/**
 * Project root directory. Assuming that framework is deployed via composer (ROOT_PATH/vendor/gears-php/framework)
 */
defined('ROOT_PATH') || define('ROOT_PATH', dirname(dirname(dirname(__DIR__))) . DS);

/**
 * Application files directory
 */
define('APP_PATH', ROOT_PATH . 'app' . DS);

/**
 * Configuration files directory
 */
define('CONF_PATH', APP_PATH . 'config' . DS);

/**
 * Public (web accessible) directory
 */
define('PUBLIC_PATH', realpath('.'));

/**
 * Base application url
 */
define('APP_URI', rtrim(dirname($_SERVER['PHP_SELF']), DS) . '/');