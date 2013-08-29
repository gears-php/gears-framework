<?php
/**
 * @author Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gf\View\Parser\State;
use Gf\View\Parser\State;
use Gf\View\Parser;

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