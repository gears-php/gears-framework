<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;
use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;

class TagQuoteClose extends State
{
    public function getProcessedBuffer(): string
    {
        return ',';
    }

    /**
     * @throws InvalidCharacter
     */
    public function run($char, Parser $parser)
    {
        if ('\'' == $char || '"' == $char) {
            $this->addBuffer($char);
        } elseif (' ' == $char) {
            $parser->switchState(TagSpace::class);
        } elseif ('/' == $char || '>' == $char) {
            $parser->switchState(TagClose::class);
        } elseif (preg_match('/\w/', $char)) {
            $parser->switchState(TagAttr::class);
        } else {
            $this->invalidCharacterException();
        }
    }
}