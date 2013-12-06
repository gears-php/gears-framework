<?php
use Gears\Framework\App\Autoloader;

require_once __DIR__ . '/../src/App/Autoloader.php';

Autoloader::registerNamespaces([
    'Gears\Framework' => __DIR__,
    'Gears\Config' => __DIR__ . '/../component/config',
    'Gears\Db' => __DIR__ . '/../component/db'
]);