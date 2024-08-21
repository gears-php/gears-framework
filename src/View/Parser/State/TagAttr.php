<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;
use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;

class TagAttr extends State
{
    public function getProcessedBuffer(): string
    {
        return sprintf('\'%s\'=>', rtrim($this->getBuffer(), '='));
    }

    /**
     * @throws InvalidCharacter
     */
    public function run($char, Parser $parser)
    {
        if ('=' == $char) {
            $this->addBuffer($char);
        } elseif ($parser->isChar('=\'', -1) || $parser->isChar('="', -1)) {
            $parser->switchState(TagQuoteOpen::class);
        } elseif ('/' == $char || '>' == $char) {
            $parser->switchState(TagClose::class);
        } elseif ($parser->isChar('<?=')) {
            $parser->nextChars(3);
            $parser->switchState(Php::class);
        } elseif (preg_match('/[\w:-]/', $char)) {
            $this->addBuffer($char);
        } else {
            $this->invalidCharacterException();
        }
    }
}