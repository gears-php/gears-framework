<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

/**
 * @noinspection PhpUnused
 */

final class Block extends AbstractTag
{
    protected string $name = 'block';

    public function render(array $attrs, array $childNodes, bool $isVoid): string
    {
        // TODO: Implement run() method.
    }
}