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
 *  <tag attr=<?= $var->value ?>>
 * ```
 */
class Php extends State
{
    public function process($char, Parser $parser): void
    {
        if ($parser->isChar('?>')) {
            $parser->nextChars(2);
            $parser->switchState(TagQuoteClose::class);
        } else {
            $this->addBuffer($char);
        }
    }

}