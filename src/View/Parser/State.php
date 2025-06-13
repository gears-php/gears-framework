<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

namespace Gears\Framework\View\Parser;

use Gears\Framework\View\Parser;
use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;

abstract class State
{
    protected string $buffer = '';
    protected array $node = [];

    /**
     * Add character to the state buffer
     */
    public function addBuffer($char): void
    {
        $this->buffer .= $char;
    }

    public function getNode(): array
    {
        return [
            'type' => get_called_class(),
            'buffer' => $this->buffer,
        ];
    }

    /**
     * Clear state by emptying all temp data
     */
    public function clear(): void
    {
        $this->buffer = '';
        $this->node = [];
    }

    /**
     * If state is the one with a given name
     */
    public function is(string $stateName): bool
    {
        return $this->getName() == $stateName;
    }

    /**
     * @throws InvalidCharacter
     */
    public function invalidCharacterException(Parser $parser)
    {
        throw new InvalidCharacter(
            get_called_class(),
            $parser->getChar(),
            implode(':', $parser->getCharPosition()),
            $parser->getFile()
        );
    }

    public function getName(): string
    {
        return static::class;
    }

    /**
     * Process current input stream character
     */
    abstract public function process($char, Parser $parser): void;
}
