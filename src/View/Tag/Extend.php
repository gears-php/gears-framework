<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

final class Extend extends AbstractTag
{
    protected string $name = 'extends';

    public function process(array $attrs, string $innerHTML, bool $isVoid): void
    {
        $this->template->extends($attrs['name']);
    }
}