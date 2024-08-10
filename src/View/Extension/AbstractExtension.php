<?php

declare(strict_types=1);

namespace Gears\Framework\View\Extension;


use Gears\Framework\View\View;

abstract class AbstractExtension implements ExtensionInterface
{
    public function setup(View $view)
    {
    }
}