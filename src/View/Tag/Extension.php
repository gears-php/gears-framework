<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

final class Extension extends AbstractTag
{
    protected string $name = 'extension';

    public function process(array $attrs, string $innerHTML, bool $isVoid): void
    {
        echo $this->template->getView()->extension($attrs['name']);
    }
}