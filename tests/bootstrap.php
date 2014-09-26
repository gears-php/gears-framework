<?php
use Gears\Framework\Application\Autoloader;

require_once __DIR__ . '/../src/Application/Autoloader.php';

Autoloader::registerNamespaces([
    'Gears\Framework' => __DIR__,
    'Gears\Config' => __DIR__ . '/../component/config/src',
    'Gears\Db' => __DIR__ . '/../component/db/src'
]);
