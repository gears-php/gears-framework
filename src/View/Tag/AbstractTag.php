<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

use Gears\Framework\View\View;

abstract class AbstractTag
{
    protected string $name;

    public function __construct(protected View $view)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function render(array $attrs, array $childNodes, bool $isVoid): string;

}