<?php

namespace Gears\Framework\View\Extension;

interface ExtensionInterface
{
    public function getName(): string;
    public function __invoke(): string;
}
