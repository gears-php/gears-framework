<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

class TagAttrValue extends State
{
    /** @var TagQuoteOpen|null */
    protected ?State $prevState;

    public function getProcessedBuffer(): string
    {
        return $this->getBuffer() ? sprintf('\'%s\'', addslashes($this->getBuffer())) : '';
    }

    public function run($char, Parser $parser)
    {
        if ($parser->getState(TagQuoteOpen::class)->getQuoteSymbol() == $char) {
            $parser->switchState(TagQuoteClose::class);
        } elseif ('{' == $char) {
            $parser->nextChar();
            $parser->switchState(Php::class);
        } else {
            $this->addBuffer($char);
        }
    }
}