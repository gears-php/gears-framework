<?php
use Gears\Framework\App\Autoloader;
use Gears\Framework\App\App;

error_reporting(E_ALL);

ini_set('display_errors', 'on');

date_default_timezone_set('UTC');

mb_internal_encoding("UTF-8");

require_once 'defines.php';
require_once 'classes/App/Autoloader.php';

Autoloader::registerNamespaces([
  'Gears\Framework' => __DIR__ . DS . 'classes',
  'app' => APP_PATH
]);

return new App();
