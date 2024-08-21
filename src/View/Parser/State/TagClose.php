<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;
use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;

class TagClose extends State
{
    public function getProcessedBuffer(): string
    {
        return ']);?>';
    }

    /**
     * @throws InvalidCharacter
     */
    public function run($char, Parser $parser)
    {
        if ('/' == $char) {
            $this->addBuffer($char);
            $char = $parser->nextChar();
        }
        if ('>' == $char) {
            $this->addBuffer($char);
        } elseif ($parser->isChar('>', -1)) {
            $parser->switchState(Read::class);
        } else {
            $this->invalidCharacterException();
        }
    }
}
