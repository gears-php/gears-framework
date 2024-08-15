<?php

declare(strict_types=1);

namespace Gears\Framework\Application;

use Gears\Storage\Storage;

abstract class AbstractServiceSetup
{
    public function __construct(
        protected readonly Storage $config,
        protected readonly ServiceContainer $services,
    ) {
    }

    abstract public function setup();
}