<?php
/**
 * @author Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

class Tag extends State
{
    public function getProcessedBuffer()
    {
        $buffer = ltrim($this->getBuffer(), '<');

        $closingTagPrefix = '';
        if (0 === strpos($buffer, '/')) {
            $buffer = ltrim($buffer, '/');
            $closingTagPrefix = 'end';
        }
		return sprintf('<?= $this->t%s([', ucfirst($closingTagPrefix . $buffer));
    }

    public function run($char, Parser $parser)
    {
        if ('<' == $char) {
            $this->addBuffer($char);
        } elseif ($parser->isChar('</', -1)) {
            $this->addBuffer('/');
        } elseif (' ' == $char) {
            $parser->switchState('TagSpace');
        } elseif ('/' == $char || '>' == $char) {
            $parser->switchState('TagClose');
        } elseif (preg_match('/[a-z0-9]/', $char)) {
            $this->addBuffer($char);
        } else {
            $this->invalidCharacterException();
        }
    }
}