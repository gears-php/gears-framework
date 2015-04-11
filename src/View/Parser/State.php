<?php
/**
 * @author Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\View\Parser;

use Gears\Framework\View\Parser;
use Gears\Framework\View\Parser\State\Exception\InvalidCharacter;

abstract class State
{
    protected $buffer = '';
    protected $name = '';
    protected $parser;
    protected $prevState;

    /**
     * Add character to the state buffer
     * @param $char
     */
    public function addBuffer($char)
    {
        $this->buffer .= $char;
    }

    /**
     * Get state buffer
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Get processed buffer
     */
    public function getProcessedBuffer()
    {
        return $this->getBuffer();
    }

    /**
     * Clean buffer
     */
    public function cleanBuffer()
    {
        $this->buffer = '';
    }

    /**
     * If state is the one with a given name
     * @param $stateName
     * @return bool
     */
    public function is($stateName)
    {
        return $this->name == $stateName;
    }

    /**
     * @return Parser
     */
    public function parser()
    {
        return $this->parser;
    }

    /**
     * @param State $state
     */
    public function setPrevState(State $state = null)
    {
        $this->prevState = $state;
    }

    /**
     * @return State
     */
    public function getPrevState()
    {
        return $this->prevState;
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

    /**
     * @param \Gears\Framework\View\Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
        $this->name = basename(str_replace('\\', DS, get_called_class()));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Process current input stream character
     * @param $char
     * @param \Gears\Framework\View\Parser $parser
     * @return mixed
     */
    abstract public function run($char, Parser $parser);
}
