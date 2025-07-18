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

    public function processNode(array $node): void
    {
        ob_start();
        foreach ($node['child_nodes'] ?? [] as $child) {
            $this->template->renderNode($child);
        }

        $this->process($node['attrs'] ?? [], trim(ob_get_clean()), $node['void']);
    }

    abstract public function process(array $attrs, string $innerHTML, bool $isVoid): void;
}