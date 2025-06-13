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
    /** @var bool no value attribute */
    private bool $empty = false;

    public function getProcessedBuffer(): string
    {
        $buff = sprintf('\'%s\'=>%s', rtrim($this->getBuffer(), '='), $this->empty ? 'null,' : '');
        $this->empty = false;
        return $buff;
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
            $this->empty = true;
            $parser->switchState(TagEnd::class);
        } elseif ($parser->isChar('<?=')) {
            $parser->nextChars(3);
            $parser->switchState(Php::class);
        } elseif (' ' == $char) {
            $this->empty = true;
            $parser->switchState(TagSpace::class);
        } elseif (preg_match('/[\w:-]/', $char)) {
            $this->addBuffer($char);
        } else {
            $this->invalidCharacterException();
        }
    }
}