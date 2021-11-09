<?php

namespace Gears\Framework\View;

interface ExtensionInterface
{
    public function getName(): string;
    public function __invoke(): string;
}
