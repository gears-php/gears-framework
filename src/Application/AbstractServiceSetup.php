<?php

declare(strict_types=1);

namespace Gears\Framework\Application;

abstract class AbstractServiceSetup
{
    use ServiceAware;

    abstract public function setup();
}