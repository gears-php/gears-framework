<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

/**
 * Include another template into current template
 */
final class IncludeTag extends AbstractTag
{
    protected string $name = 'include';

    public function process(array $attrs, string $innerHTML, bool $isVoid): void
    {
        if ($isVoid) {
            echo $this->template->getView()->load($attrs['name'])->render($this->template->getVars());
        } else {
            echo $this->template->getView()->load($innerHTML)->render($this->template->getVars());
        }
    }
}