<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;
use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;

/**
 * Tag ending symbols processing
 */
class TagEnd extends State
{
    /** @var bool if this is self-closing (empty) tag */
    private bool $void = false;

    public function clear(): void
    {
        parent::clear();
        $this->void = false;
    }

    /**
     * @throws InvalidCharacter
     */
    public function process($char, Parser $parser): void
    {
        if ('/' == $char) {
            $char = $parser->nextChar();
            $this->void = true;
        }
        if ('>' == $char) {
            $parser->switchState(Stop::class);
            return;
        }
        $this->invalidCharacterException($parser);
    }

    public function getNode(): array
    {
        return parent::getNode() + [
                'void' => $this->void,
            ];
    }
}
