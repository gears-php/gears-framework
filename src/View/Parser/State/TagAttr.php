<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;
use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;

class TagAttr extends State
{
    /**
     * @throws InvalidCharacter
     */
    public function process($char, Parser $parser): void
    {
        if ('=' == $char) {
            return;
        } elseif ($parser->isChar('=\'', -1) || $parser->isChar('="', -1)) {
            $parser->switchState(TagQuoteOpen::class);
        } elseif ('/' == $char || '>' == $char) {
            $parser->switchState(TagEnd::class);
        } elseif (' ' == $char) {
            $parser->switchState(TagSpace::class);
        } elseif (preg_match('/[\w:-]/', $char)) {
            $this->addBuffer($char);
        } else {
            $this->invalidCharacterException($parser);
        }
    }
}