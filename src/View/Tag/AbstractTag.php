<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

use Gears\Framework\View\Template;

abstract class AbstractTag
{
    protected string $name;

    public function __construct(protected Template $template)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function process(array $attrs, string $innerHTML, bool $isVoid): void;

}