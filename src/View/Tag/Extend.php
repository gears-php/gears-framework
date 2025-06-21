<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

/**
 * @noinspection PhpUnused
 */

final class Extend extends AbstractTag
{
    protected string $name = 'extend';

    public function render(array $attrs, array $childNodes, bool $isVoid): string
    {
        // TODO: Implement run() method.
    }
}