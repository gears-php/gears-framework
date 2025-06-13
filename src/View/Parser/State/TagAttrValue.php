<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

class TagAttrValue extends State
{

    public function process($char, Parser $parser): void
    {
        if ($parser->getState(TagQuoteOpen::class)->getQuoteSymbol() == $char) {
            $parser->switchState(TagQuoteClose::class);
        } else {
            $this->addBuffer($char);
        }
    }
}