<?php

declare(strict_types=1);

namespace Gears\Framework\View\Tag;

use Gears\Framework\View\RenderingException;

final class Iterate extends AbstractTag
{
    protected string $name = 'iterate';

    public function processNode(array $node): void
    {
        $sourceVar = key($node['attrs']);
        $sourceCollection = $this->template->getVar($sourceVar);
        if (!is_iterable($sourceCollection)) {
            throw new RenderingException(
                sprintf(
                    'Template variable "%s" is not iterable. Defined in %s:%d',
                    $sourceVar,
                    $this->template->getFilePath(),
                    $node['tag_pos'][0]
                )
            );
        }
        $destVar = current($node['attrs']);
        $backupVar = $this->template->getVar($destVar);
        foreach ($sourceCollection as $value) {
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