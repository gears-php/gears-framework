<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

/**
 * State for pure PHP code processing inside tag attribute value.
 * Supported forms:
 *
 * ```php
 *  <tag attr="{$var->value}">
 *  <tag attr=<?= $var->value ?>>
 * ```
 */
class Php extends State
{
    public function getProcessedBuffer(): string
    {
        return trim($this->getBuffer()) ?: 'null';
    }

    public function run($char, Parser $parser)
    {
        if ($parser->isChar('?>') && $this->prevState instanceof TagAttr) {
            $parser->nextChars(2);
            $parser->switchState(TagQuoteClose::class);
        } elseif ('}' == $char && $this->prevState instanceof TagAttrValue) {
            $parser->nextChar();
            $parser->switchState(TagAttrValue::class);
        } else {
            $this->addBuffer($char);
        }
    }
}