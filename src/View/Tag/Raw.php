<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

final class Raw extends AbstractTag
{
    protected string $name = 'raw';

    public function process(array $attrs, string $innerHTML, bool $isVoid): void
    {
        echo htmlspecialchars_decode($innerHTML);
    }
}