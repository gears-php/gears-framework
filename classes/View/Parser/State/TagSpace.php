<?php
/**
 * @author Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

class TagSpace extends State
{
    public function getProcessedBuffer()
    {
        return '';
    }

    public function run($char, Parser $parser)
    {
        if (' ' == $char) {
            $this->addBuffer($char);
        } elseif (preg_match('/\w/', $char)) {
            $parser->switchState('TagAttr');
        } elseif ('/' == $char || '>' == $char) {
            $parser->switchState('TagClose');
        } else {
            $this->invalidCharacterException();
        }
    }
}