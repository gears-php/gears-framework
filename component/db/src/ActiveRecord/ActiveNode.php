<?php

declare(strict_types=1);

namespace Gears\Db\ActiveRecord;

class ActiveNode extends ActiveRecord
{
    private array $children = [];
    private string $childrenSerializeKey = '$children';

    public function addChild(ActiveNode $child)
    {
        $this->children[] = $child;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function jsonSerialize()
    {
        return array_merge(
            parent::jsonSerialize(),
            [
                $this->childrenSerializeKey => $this->children,
            ]
        );
    }
}