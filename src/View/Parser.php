<?php
/**
 * @author: Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
declare(strict_types=1);

namespace Gears\Framework\View;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser\State\Read;
use Gears\Framework\View\Parser\State\TagClose;

class Parser
{
    /**
     * Path to initial file
     */
    protected string $file;

    /**
     * Input stream
     */
    protected string $stream = '';

    /**
     * Parsed template output stream
     */
    protected string $buffer = '';

    /**
     * Current stream character offset
     */
    protected int $offset = 0;

    /**
     * Character at current stream offset
     */
    protected string $char = '';

    protected array $states = [];

    /**
     * Current state object
     */
    protected ?State $currentState = null;

    protected int $offsetCorrection = 0;

    /**
     * List of all special template language tags to be processed
     */
    protected array $tags = ['extends', 'block', 'include', 'repeat', 'extension'];

    /**
     * Initialize parser with a new stream
     */
    public function init(string $stream): void
    {
        $this->stream = str_replace(["\r\n", "\r"], "\n", $stream);
        $this->offsetCorrection = 0;
    }

    /**
     * Process template tag turning it into corresponding template method call
     */
    public function processTag(int $startOffset): void
    {
        // take into account offset correction from previous processed tag
        $startOffset += $this->offsetCorrection;
        $this->offset = $startOffset - 1;
        $this->buffer = '';

        $this->switchState(Read::class);

        while (!$this->currentState instanceof TagClose && $this->nextChar()) {
            $this->state($this->currentState->getName());
        }

        $this->switchState(Read::class);

        // clean (initial) tag length
        $tagLength = $this->offset - $startOffset + 1;
        // processed tag buffer length
        $bufferLength = strlen($this->getBuffer());
        // replace initial tag with processed tag code
        $this->stream = substr_replace($this->stream, $this->getBuffer(), $startOffset, $tagLength);
        // adjust offset correction for next tag processing iteration
        $this->offsetCorrection += $bufferLength - $tagLength;
    }

    /**
     * Read file and process its content
     * @param string $filePath Full path to the file to be processed
     * @return string Processed content
     */
    public function parseFile(string $filePath): string
    {
        $this->file = $filePath;
        return $this->parse(file_get_contents($filePath));
    }

    /**
     * Process input stream by parsing special tags of template language and return processed one.
     */
    public function parse(string $stream): string
    {
        // initialize parser with an input stream
        $this->init($stream);

        // remove all php code inclusions
        $cleanStream = preg_replace_callback('/(<\?(?:.*?)\?>)/s', function ($phpcode) {
            return str_repeat(' ', strlen($phpcode[0]));
        }, $this->stream);

        // capture all template tags start positions
        preg_match_all(
            sprintf('/<\/?(?:%s)[ >]/', implode('|', $this->tags)),
            $cleanStream,
            $tagOffsets,
            PREG_OFFSET_CAPTURE
        );
        unset($cleanStream);

        foreach ($tagOffsets[0] as $tagOffset) {
            $this->processTag($tagOffset[1]);
        }

        return $this->stream;
    }

    /**
     * Get current stream offset position
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getPosition(): string
    {
        $chunk = substr($this->stream, 0, $this->offset);
        $line = substr_count($chunk, "\n") + 1;
        $lineOffset = $this->offset - strrpos($chunk, "\n");
        return $line . ':' . $lineOffset;
    }

    /**
     * Read next stream character by moving forward on input stream.
     */
    public function nextChar(): string
    {
        return $this->char = ($this->stream[++$this->offset] ?? '');
    }

    /**
     * Get next $count of characters by moving forward on input stream.
     */
    public function nextChars(int $count = 1): string
    {
        $count >= 1 || throw new \ValueError('Given count must be 1 or greater.');
        $chars = '';

        while ($count-- && ($char = $this->nextChar())) {
            $chars .= $char;
        }

        return $chars;
    }

    /**
     * Get current stream character
     */
    public function getChar(): string
    {
        return $this->char;
    }

    /**
     * Get stream character(s) by the given offset
     * @param int $offset (optional) Offset value relative to the current inner offset
     * @param int $count (optional) Number of characters to take
     */
    public function getCharAt(int $offset = 0, int $count = 1): string
    {
        $offset += $this->getOffset();
        if ($offset >= 0) {
            return substr($this->stream, $offset, $count);
        }
        return '';
    }

    /**
     * Match given character(s) with the ones at specific relative offset
     * @param string $char Character or character string
     * @param int $offset (optional) Offset value relative to the current inner offset
     * @return bool
     */
    public function isChar(string $char, int $offset = 0): bool
    {
        return $char == $this->getCharAt($offset, strlen($char));
    }

    /**
     * Run state with a given name
     */
    public function state(string $stateName): void
    {
        $this->getState($stateName)->run($this->getChar(), $this);
    }

    /**
     * Switch to a new state
     */
    public function switchState(string $stateName): void
    {
        if ($this->currentState) {
            $this->addBuffer($this->currentState->getProcessedBuffer());
        }

        $newState = $this->getState($stateName);
        $newState->setPrevState($this->currentState);
        $newState->cleanBuffer();

        $this->currentState = $newState;
        $this->state($this->currentState->getName());
    }

    /**
     * Get state by a given state name
     * @template T of State
     * @param class-string<T> $stateClass
     * @return T
     */
    public function getState(string $stateClass): State
    {
        if (!isset($this->states[$stateClass])) {
            $this->states[$stateClass] = new $stateClass($this);
        }
        return $this->states[$stateClass];
    }

    /**
     * Return the name of input stream file
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Return final output
     */
    public function getBuffer(): string
    {
        return $this->buffer;
    }

    /**
     * Add parser buffer
     */
    public function addBuffer(string $chars): void
    {
        $this->buffer .= $chars;
    }
}
