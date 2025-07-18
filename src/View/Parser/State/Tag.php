<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;
use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;

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
     * @throws InvalidCharacter
     * {@inheritDoc}
     */
    public function process($char, Parser $parser): void
    {
        if ('<' == $char) {
            return;
        } elseif ($parser->isChar('</', -1)) {
            $this->closingTag = true;
        } elseif (' ' == $char) {
            $parser->switchState(TagSpace::class);
        } elseif ('/' == $char || '>' == $char) {
            $parser->switchState(TagEnd::class);
        } elseif (preg_match('/[a-z0-9_-]/', $char)) {
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