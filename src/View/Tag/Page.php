<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

/**
 * @noinspection PhpUnused
 */

// todo move to app-level after implementation
final class Page extends AbstractTag
{
    protected string $name = 'page';

    public function render(array $attrs, array $childNodes, bool $isVoid): string
    {
        return implode(array_keys($attrs, null));
    }
}