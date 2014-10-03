<?php
use Gears\Framework\Application\ClassLoader;

require_once __DIR__ . '/../src/Application/ClassLoader.php';

ClassLoader::registerNamespaces([
    'Gears\Framework' => __DIR__,
    'Gears\Config' => __DIR__ . '/../component/config/src',
    'Gears\Db' => __DIR__ . '/../component/db/src'
]);
