<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;
use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;

class TagEnd extends State
{
    /** @var bool if this is self-closing (empty) tag */
    private bool $void = false;

    public function getProcessedBuffer(): string
    {
        $buff = sprintf('"_void" => %b]);?>', $this->void);
        $this->void = false;
        return $buff;
    }

    /**
     * @throws InvalidCharacter
     */
    public function run($char, Parser $parser)
    {
        if ('/' == $char) {
            $char = $parser->nextChar();
            $this->void = true;
        }
        if ('>' == $char) {
            return;
        }
        if ($parser->isChar('>', -1)) {
            $parser->switchState(Read::class);
        } else {
            $this->invalidCharacterException();
        }
    }
}
