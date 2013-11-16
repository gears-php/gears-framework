<?php
use Gears\Framework\App\Autoloader;
use Gears\Framework\App\App;

error_reporting(E_ALL);

ini_set('display_errors', 'on');

date_default_timezone_set('UTC');

mb_internal_encoding("UTF-8");

require_once 'defines.php';
require_once 'classes/App/Autoloader.php';

(new Autoloader('Gears\Framework', __DIR__ . DS . 'classes'))->register();

if (is_file(APP_PATH . 'Bootstrap.php')) {
    require_once APP_PATH . 'Bootstrap.php';
    return new Bootstrap(new App());
} else {
    return new \Gears\Framework\App\Bootstrap(new App());
}

