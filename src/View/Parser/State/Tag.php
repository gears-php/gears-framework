<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;
use Gears\Framework\View\Exception\TemplateSyntaxException;

class Tag extends State
{
    /** @var bool if this is a closing tag */
    private bool $closingTag = false;


    public function clear(): void
    {
        parent::clear();
        $this->closingTag = false;
    }

    /**
     * @throws TemplateSyntaxException
     * {@inheritDoc}
     */
    public function process($char, Parser $parser): void
    {
        $ord = ord($char);
        if ('<' == $char) {
            return;
        } elseif ($parser->isChar('</', -1)) {
            $this->closingTag = true;
        } elseif (' ' === $char) {
            $parser->switchState(TagSpace::class);
        } elseif ('/' === $char || '>' === $char) {
            $parser->switchState(TagEnd::class);
        } elseif (
            ($ord >= 97 && $ord <= 122) || // a-z
            ($ord >= 48 && $ord <= 57) || // 0-9
            '_' === $char ||
            '-' === $char
        ) {
            $this->addBuffer($char);
        } else {
            $this->invalidCharacterException($parser);
        }
    }

    public function getNode(): array
    {
        return parent::getNode() + [
                'closing' => $this->closingTag,
            ];
    }
}