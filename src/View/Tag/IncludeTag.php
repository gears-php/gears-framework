<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

/**
 * Include another template into current template
 *
 * @noinspection PhpUnused
 */
final class IncludeTag extends AbstractTag
{
    protected string $name = 'include';

    public function render(array $attrs, array $childNodes, bool $isVoid): string
    {
        if ($isVoid) {
            return '// todo include';
        }

        return $this->view->load($attrs['name'])->render(
            array_diff_key($attrs, array_flip(['name']))
        );
    }
}