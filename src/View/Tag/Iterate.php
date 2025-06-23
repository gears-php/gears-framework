<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

/**
 * @noinspection PhpUnused
 */

final class Iterate extends AbstractTag
{
    protected string $name = 'iterate';

    public function processNode(array $node): void
    {
        $sourceVar = key($node['attrs']);
        $destVar = current($node['attrs']);
        $backupVar = $this->template->getVar($destVar);
        foreach ($this->template->getVar($sourceVar) as $value) {
            $this->template->setVar($destVar, $value);
            parent::processNode($node);
        }
        $this->template->setVar($destVar, $backupVar);
    }

    public function process(array $attrs, string $innerHTML, bool $isVoid): void
    {
        echo $innerHTML;
    }
}