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
    /** @var bool if this is a closing tag */
    private bool $closingTag = false;

    /**
     * {@inheritDoc}
     */
    public function getProcessedBuffer(): string
    {
        $buffer = sprintf(
            '<?= $this->t%s(["_tag_pos" => %d, ',
            ($this->closingTag ? 'End' : '') . ucfirst($this->getBuffer()),
            $this->parser()->getPosition(),
        );
        $this->closingTag = false;
        return $buffer;
    }

    /**
     * @throws InvalidCharacter
     * {@inheritDoc}
     */
    public function run($char, Parser $parser)
    {
        if ('<' == $char) {
            return;
        }
        if ($parser->isChar('</', -1)) {
            $this->closingTag = true;
        } elseif (' ' == $char) {
            $parser->switchState(TagSpace::class);
        } elseif ('/' == $char || '>' == $char) {
            $parser->switchState(TagEnd::class);
        } elseif (preg_match('/[a-z0-9]/', $char)) {
            $this->addBuffer($char);
        } else {
            $this->invalidCharacterException();
        }
    }
}