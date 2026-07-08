<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;
use Gears\Framework\View\Exception\TemplateSyntaxException;

class TagAttr extends State
{
    /**
     * @throws TemplateSyntaxException
     */
    public function process($char, Parser $parser): void
    {
        $ord = ord($char);
        if ('=' === $char) {
            return;
        } elseif ($parser->isChar('=\'', -1) || $parser->isChar('="', -1)) {
            $parser->switchState(TagQuoteOpen::class);
        } elseif ('/' === $char || '>' === $char) {
            $parser->switchState(TagEnd::class);
        } elseif (' ' === $char) {
            $parser->switchState(TagSpace::class);
        } elseif (
            ($ord >= 97 && $ord <= 122) || // a-z
            ($ord >= 65 && $ord <= 90) || // A-Z
            ($ord >= 48 && $ord <= 57) || // 0-9
            '_' === $char ||
            '-' === $char ||
            ':' === $char
        ) {
            $this->addBuffer($char);
        } else {
            $this->invalidCharacterException($parser);
        }
    }
}