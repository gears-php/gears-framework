<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

class TagAttr extends State
{
    public function getProcessedBuffer()
    {
        return sprintf('\'%s\'=>', rtrim($this->getBuffer(), '='));
    }

    public function run($char, Parser $parser)
    {
        if ('=' == $char) {
            $this->addBuffer($char);
        } elseif ($parser->isChar('=\'', -1) || $parser->isChar('="', -1)) {
            $parser->switchState('TagQuoteOpen');
        } elseif ('/' == $char || '>' == $char) {
            $parser->switchState('TagClose');
        } elseif (preg_match('/[\w:-]/', $char)) {
            $this->addBuffer($char);
        } else {
            $this->invalidCharacterException();
        }
    }
}