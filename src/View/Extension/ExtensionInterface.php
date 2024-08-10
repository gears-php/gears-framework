<?php

namespace Gears\Framework\View\Extension;

use Gears\Framework\View\View;

interface ExtensionInterface
{
    public function getName(): string;
    public function __invoke(): string;
    public function setup(View $view);
}
