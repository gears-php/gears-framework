<?php
use Gears\Framework\App\Autoloader;

ini_set('display_errors', 'On');

error_reporting(E_ALL);

date_default_timezone_set('UTC');

mb_internal_encoding("UTF-8");

require_once 'defines.php';
require_once 'classes/App/Autoloader.php';

Autoloader::registerNamespaces(['Gears\Framework' => __DIR__ . DS . 'classes', 'app' => APP_PATH]);
//(new Autoloader())->register(); // default loader