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
    /**
     * @throws InvalidCharacter
     */
    public function process($char, Parser $parser): void
    {
        if ('\'' == $char || '"' == $char) {
            $this->addBuffer($char);
        } elseif (' ' == $char) {
            $parser->switchState(TagSpace::class);
        } elseif ('/' == $char || '>' == $char) {
            $parser->switchState(TagEnd::class);
        } elseif (preg_match('/\w/', $char)) {
            $parser->switchState(TagAttr::class);
        } else {
            $this->invalidCharacterException($parser);
        }
    }
}