<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

/**
 * @noinspection PhpUnused
 */

final class Extension extends AbstractTag
{
    protected string $name = 'extension';

    public function render(array $attrs, array $childNodes, bool $isVoid): string
    {
        return $this->view->extension($attrs['name']);
    }
}