<?php
/**
 * @author Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gf\View\Parser\State;
use Gf\View\Parser\State;
use Gf\View\Parser;

class TagClose extends State
{
    public function getProcessedBuffer()
    {
        return ']);?>';
    }

    public function run($char, Parser $parser)
    {
        if ('/' == $char) {
            $this->addBuffer($char);
        } elseif ('>' == $char) {
            $this->addBuffer($char);
        } elseif ($parser->isChar('>', -1)) {
            $parser->switchState('Read');
        } else {
            $this->invalidCharacterException();
        }
    }
}
