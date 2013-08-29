<?php
use Gf\Core\Autoloader;

ini_set('display_errors', 'On');

error_reporting(E_ALL);

date_default_timezone_set('UTC');

mb_internal_encoding("UTF-8");

require_once 'defines.php';
require_once 'Gf/Core/Autoloader.php';

Autoloader::registerNamespaces(['Gf' => __DIR__, 'app' => ROOT_PATH]);
//(new Autoloader())->register(); // default loader