<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;
use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;

class Tag extends State
{
    public function getProcessedBuffer(): string
    {
        $buffer = ltrim($this->getBuffer(), '<');

        $closingTagPrefix = '';
        if (str_starts_with($buffer, '/')) {
            $buffer = ltrim($buffer, '/');
            $closingTagPrefix = 'end';
        }
        return sprintf('<?= $this->t%s(["_meta" => ["tag_pos" => %d], ', ucfirst($closingTagPrefix . $buffer), $this->parser()->getPosition());
    }

    /**
     * @throws InvalidCharacter
     */
    public function run($char, Parser $parser)
    {
        if ('<' == $char) {
            $this->addBuffer($char);
        } elseif ($parser->isChar('</', -1)) {
            $this->addBuffer('/');
        } elseif (' ' == $char) {
            $parser->switchState(TagSpace::class);
        } elseif ('/' == $char || '>' == $char) {
            $parser->switchState(TagClose::class);
        } elseif (preg_match('/[a-z0-9]/', $char)) {
            $this->addBuffer($char);
        } else {
            $this->invalidCharacterException();
        }
    }
}