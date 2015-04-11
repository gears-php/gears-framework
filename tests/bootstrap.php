<?php
use Gears\Framework\Application\ClassLoader;

require_once __DIR__ . '/../src/Application/ClassLoader.php';

ClassLoader::registerMappings([
    'Gears\Framework' => __DIR__,
    'Gears\Storage' => __DIR__ . '/../component/storage/src',
    'Gears\Db' => __DIR__ . '/../component/db/src'
]);
