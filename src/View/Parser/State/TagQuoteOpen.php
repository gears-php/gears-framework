<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

class TagQuoteOpen extends State
{
    private string $quoteSymbol = '';

    public function getQuoteSymbol(): string
    {
        return $this->quoteSymbol;
    }

    public function process($char, Parser $parser): void
    {
        // empty quotes
        if ($parser->isChar('\'\'', -1) || $parser->isChar('""', -1)) {
            $parser->switchState(TagQuoteClose::class);
        } elseif ('\'' == $char || '"' == $char) {
            $this->quoteSymbol = $char;
            $this->addBuffer($char);
            $parser->nextChar();
            $parser->switchState(TagAttrValue::class);
        }
    }
}