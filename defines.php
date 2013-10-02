<?php
/**
 * Shortcut for native DIRECTORY_SEPARATOR constant
 */
defined('DS') || define('DS', DIRECTORY_SEPARATOR);

/**
 * Project root directory. Assuming that current working dir is one step down from the root
 */
defined('ROOT_PATH') || define('ROOT_PATH', dirname(getcwd()) . DS);

/**
 * Application files directory
 */
define('APP_PATH', ROOT_PATH . 'app' . DS);

/**
 * Configuration files directory
 */
define('CONF_PATH', APP_PATH . 'config' . DS);

/**
 * Base project url
 */
define('BASE_URL', ($_SERVER['SCRIPT_NAME'] == $_SERVER['PHP_SELF'] ? '' : $_SERVER['SCRIPT_NAME']) . '/');