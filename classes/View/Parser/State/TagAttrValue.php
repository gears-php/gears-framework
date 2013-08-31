<?php
/**
 * @author Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

class TagAttrValue extends State
{
    public function getProcessedBuffer()
    {
        return sprintf('\'%s\'', addslashes($this->getBuffer()));
    }

    public function run($char, Parser $parser)
    {
        $quoteOpen = $parser->getState('TagQuoteOpen')->getQuoteSymbol();
        if ($quoteOpen == $char) {
            $parser->switchState('TagQuoteClose');
        } elseif ($parser->isChar('{')) {
            $parser->switchState('Php');
        } else {
            $this->addBuffer($char);
        }
    }
}