<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

/**
 * @noinspection PhpUnused
 */

final class Block extends AbstractTag
{
    protected string $name = 'block';

    public function process(array $attrs, string $innerHTML, bool $isVoid): void
    {
        $this->template->getParent()?->setBlockContent($attrs['name'], $innerHTML);

        echo $this->template->getBlockContent($attrs['name']) ?: $innerHTML;
    }
}