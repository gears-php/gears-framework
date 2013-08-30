<?php
/**
 * @author: Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\View;
use Gears\Framework\View\Parser\State;

class Parser
{
	/**
	 * Path to initial file
	 */
	protected $file = null;

	/**
	 * Input stream
	 * @var string
	 */
	protected $stream = '';

	/**
	 * Parsed template output stream
	 * @var string
	 */
	protected $buffer = '';

	/**
	 * Current stream character offset
	 * @var int
	 */
	protected $offset = 0;

	/**
	 * Character at current stream offset
	 * @var bool|string(1)
	 */
	protected $char = false;

	/**
	 * @var array
	 */
	protected $states = [];

	/**
	 * Current state object
	 * @var State
	 */
	protected $currentState;

	/**
	 * @var int
	 */
	protected $offsetCorrection = 0;

	/**
	 * List of all special template language tags to be processed
	 * @var string
	 */
	protected $tags = ['extends', 'block', 'include', 'repeat' ,'js', 'css', 'image'];

	/**
	 * Initialize parser with a new stream
	 * @param string $stream
	 */
	public function init($stream)
	{
		$this->stream = str_replace(["\r\n", "\r"], "\n", $stream);
		$this->offsetCorrection = 0;
	}

	/**
	 * Process template tag turning it into corresponding template method call
	 * @param $startOffset
	 */
	public function processTag($startOffset)
	{
		// take into account offset correction from previous processed tag
		$startOffset += $this->offsetCorrection;
		$this->offset = $startOffset - 1;
		$this->buffer = '';

		$this->switchState('Read');

		while (!$this->currentState->is('TagClose') && $this->readChar() !== false) {
			$this->state($this->currentState->getName());
		}

		$this->switchState('Read');

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
	 * @return Processed content
	 */
	public function parseFile($filePath)
	{
		$this->file = $filePath;
		return $this->parse(file_get_contents($filePath));
	}

	/**
	 * Process input stream by parsing special tags of template language
	 * @param string $stream
	 * @return string Processed stream
	 */
	public function parse($stream)
	{
		// initialize parser with an input stream
		$this->init($stream);

		// remove all php code inclusions
		$cleanStream = preg_replace_callback('/(<\?(?:.*?)\?>)/s', function ($phpcode) {
			return str_repeat(' ', strlen($phpcode[0]));
		}, $this->stream);

		// capture all template tags start positions
		preg_match_all(sprintf('/<\/?(?:%s)/', implode('|', $this->tags)), $cleanStream, $tagOffsets, PREG_OFFSET_CAPTURE);
		unset($cleanStream);

		foreach ($tagOffsets[0] as $tagOffset) {
			$this->processTag($tagOffset[1]);
		}

		return $this->stream;
	}

	/**
	 * Get current stream offset position
	 * @return int
	 */
	public function getOffset()
	{
		return $this->offset;
	}

	/**
	 * @return string
	 */
	public function getPosition()
	{
		$chunk = substr($this->stream, 0, $this->offset);
		$line = substr_count($chunk, "\n") + 1;
		$lineOffset = $this->offset - strrpos($chunk, "\n");
		return $line . ':' . $lineOffset;
	}

	/**
	 * Read and return next stream character. False otherwise
	 * @return string|boolean
	 */
	public function readChar()
	{
		return $this->char = isset($this->stream[++$this->offset]) ? $this->stream[$this->offset] : false;
	}

	/**
	 * Get current stream character
	 * @return string
	 */
	public function getChar()
	{
		return $this->char;
	}

	/**
	 * Get stream character(s) by the given offset
	 * @param int $offset (optional) Offset value relative to the current inner offset
	 * @param int $count (optional) Number of characters to take
	 * @return string
	 */
	public function getCharAt($offset = 0, $count = 1)
	{
		$offset += $this->getOffset();
		if ($offset >= 0) {
			return substr($this->stream, $offset, $count);
		} else {
			return false;
		}
	}

	/**
	 * Match given character(s) with the ones at specific relative offset
	 * @param string $char Character or character string
	 * @param int $offset (optional) Offset value relative to the current inner offset
	 * @return bool
	 */
	public function isChar($char, $offset = 0)
	{
		return $char == $this->getCharAt($offset, strlen($char));
	}

	/**
	 * Run state with a given name
	 * @param string $stateName
	 */
	public function state($stateName)
	{
		$this->getState($stateName)->run($this->getChar(), $this);
	}

	/**
	 * Switch to a new state
	 * @param string $stateName
	 */
	public function switchState($stateName)
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
	 * @param $stateName
	 * @return State
	 * @throws \Exception
	 */
	public function getState($stateName)
	{
		$stateName = __NAMESPACE__ . '\\Parser\\State\\' . $stateName;
		if (!isset($this->states[$stateName])) {
			$this->states[$stateName] = new $stateName($this);
		}
		return $this->states[$stateName];
	}

	/**
	 * Return the name of input stream file
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * Return final output
	 * @return string
	 */
	public function getBuffer()
	{
		return $this->buffer;
	}

	/**
	 * Add parser buffer
	 * @param string $chars
	 */
	public function addBuffer($chars)
	{
		$this->buffer .= $chars;
	}
}