<?php

declare(strict_types=1);

namespace Gears\Framework\Application;

use Gears\Storage\Storage;

abstract class AbstractServiceSetup
{
    use ServiceAware;

    abstract public function setup(Storage $config);
}