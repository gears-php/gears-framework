<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord;

class ActiveNode extends ActiveRecord
{
    public ?self $parent;
    protected array $children = [];

    public function addChild(ActiveNode $child): static
    {
        $child->setParent($this);
        $this->children[] = $child;

        return $this;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function jsonSerialize(): array
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                $this->getMetadata()->raw()['childrenSerializeKey'] ?? '$children' => $this->children,
            ]
        );
    }

    public function setParent(?ActiveNode $parent): void
    {
        $this->parent = $parent;
    }

    public function save(): bool
    {
        parent::save();

        foreach ($this->children as $childNode) {
            echo('saved child node/ ');
            $childNode->save();
        }

        return true;
    }
}