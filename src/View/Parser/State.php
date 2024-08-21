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
    protected Parser $parser;
    protected ?State $prevState;

    /**
     * Add character to the state buffer
     */
    public function addBuffer($char): void
    {
        $this->buffer .= $char;
    }

    /**
     * Get state buffer
     */
    public function getBuffer(): string
    {
        return $this->buffer;
    }

    /**
     * Get processed buffer
     */
    public function getProcessedBuffer(): string
    {
        return $this->getBuffer();
    }

    /**
     * Clean buffer
     */
    public function cleanBuffer(): void
    {
        $this->buffer = '';
    }

    /**
     * If state is the one with a given name
     */
    public function is(string $stateName): bool
    {
        return $this->getName() == $stateName;
    }

    /**
     * @return Parser
     */
    public function parser(): Parser
    {
        return $this->parser;
    }

    public function setPrevState(State $state = null): void
    {
        $this->prevState = $state;
    }

    /**
     * @throws InvalidCharacter
     */
    public function invalidCharacterException()
    {
        throw new InvalidCharacter(
            get_called_class(),
            $this->parser()->getChar(),
            $this->parser()->getPosition(),
            $this->parser()->getFile()
        );
    }

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function getName(): string
    {
        return static::class;
    }

    /**
     * Process current input stream character
     */
    abstract public function run($char, Parser $parser);
}
