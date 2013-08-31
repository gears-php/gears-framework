<?php
/**
 * @author Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

class Php extends State
{
    public function getProcessedBuffer()
    {
        $buffer = $this->getBuffer();
        if ($this->getPrevState()->is('TagAttrValue')) {
            // get rid of spaces in order to use more simpler regex
            $buffer = str_replace([' '], '', $buffer);
            // remove php-code wrapping braces
            $buffer = preg_replace('/{(.*?)}/', '$1', $buffer);
            return '.' . $buffer . '.';
        } else {
            // log invalid php occurrence
            //	log(sprintf('Buffer processing failed: invalid php code occurrence at %s position',
            //   $this->parser()->formatPosition(-strlen($buffer))));
        }
    }

    public function run($char, Parser $parser)
    {
        $this->addBuffer($char);
        if ($parser->isChar('}')) {
            $parser->readChar();
            $parser->switchState($this->getPrevState()->getName());
        }
    }
}