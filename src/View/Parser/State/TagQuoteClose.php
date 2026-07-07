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
        $ord = ord($char);
        if ('\'' === $char || '"' === $char) {
            $this->addBuffer($char);
        } elseif (' ' === $char) {
            $parser->switchState(TagSpace::class);
        } elseif ('/' === $char || '>' === $char) {
            $parser->switchState(TagEnd::class);
        } elseif (
            ($ord >= 97 && $ord <= 122) || // a-z
            ($ord >= 65 && $ord <= 90) || // A-Z
            ($ord >= 48 && $ord <= 57) || // 0-9
            $char === '_'
        ) {
            $parser->switchState(TagAttr::class);
        } else {
            $this->invalidCharacterException($parser);
        }
    }
}