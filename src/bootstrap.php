<?php

use Gears\Framework\Application\Application;
use Gears\Framework\Application\Bootstrap as DefaultBootstrap;
use Gears\Framework\Application\Request;

error_reporting(E_ALL);

ini_set('display_errors', 'on');

date_default_timezone_set('UTC');

mb_internal_encoding("UTF-8");

require_once 'defines.php';

$application = new Application(new Request);

if (is_file(APP_PATH . 'Bootstrap.php')) {
    require_once APP_PATH . 'Bootstrap.php';
    return new Bootstrap($application);
} else {
    return new DefaultBootstrap($application);
}
