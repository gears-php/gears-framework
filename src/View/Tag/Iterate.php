<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

/**
 * @noinspection PhpUnused
 */
final class Iterate extends AbstractTag
{
    protected string $name = 'iterate';

    public function process(array $attrs, string $innerHTML, bool $isVoid): void
    {
        // TODO: Implement run() method.
        echo '';
    }
}