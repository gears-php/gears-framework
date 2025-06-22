<?php

/**
 * @author: Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
declare(strict_types=1);

namespace Gears\Framework\View;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser\State\Stop;
use Gears\Framework\View\Parser\State\Tag;
use Gears\Framework\View\Parser\State\TagAttr;
use Gears\Framework\View\Parser\State\TagAttrValue;
use Gears\Framework\View\Parser\State\TagEnd;

final class Parser
{
    /**
     * Path to initial file
     */
    private string $file;

    /**
     * Input stream
     */
    private string $stream = '';

    /**
     * Nodes with all parsed tags (as array structures) and plain HTML parts (as strings)
     */
    private array $nodes = [];

    /** Index of current tag node in nodes collection */
    private int $nodeCounter = 0;

    /**
     * Current stream character offset
     */
    private int $offset = 0;

    /** @var State[] */
    private array $states = [];

    /**
     * Current state object
     */
    private ?State $currentState = null;

    /**
     * @param array $tags List of all special template language tags to process
     */
    public function __construct(private readonly array $tags)
    {
    }

    /**
     * Read file and process its content to the template meta structure
     */
    public function parseFile(string $filePath): array
    {
        $this->file = $filePath;
        return $this->parse(file_get_contents($filePath));
    }

    /**
     * Process input stream by parsing special tags of template language
     * and giving result node structure with tags metadata and raw html parts.
     */
    public function parse(string $stream): array
    {
        $this->stream = str_replace(["\r\n", "\r"], "\n", $stream);

        // capture all template tags start positions
        preg_match_all(
            sprintf('/<\/?(?:%s)[ >]/', implode('|', array_keys($this->tags))),
            $this->stream,
            $tagOffsets,
            PREG_OFFSET_CAPTURE
        );

        foreach ($tagOffsets[0] as $tagOffset) {
            $this->processHTML($tagOffset[1] - $this->offset);
            $this->processTag($tagOffset[1]);
        }

        $this->processHTML(strlen($this->stream) - $this->offset);

        return $this->reduce($this->nodes);
    }

    public function processHTML(int $length): void
    {
        $html = trim(substr($this->stream, $this->offset, $length));
        if ($html) {
            $this->nodes[$this->nodeCounter++] = ['html' => $html];
        }
    }

    /** Reduce plain array of nodes into nested structure of template tags and raw html chunks */
    public function reduce(array &$nodes): array
    {
        static $openedTags = [];
        $resultNodes = [];
        while ($node = array_splice($nodes, 0, 1)) {
            $node = end($node);
            if (isset($node['tag']) && $node['closing']) {
                $oTag = array_pop($openedTags);
                if ($oTag != $node['tag']) {
                    throw new \RuntimeException(
                        sprintf(
                            'Missing opening tag for &lt;/%s&gt; tag at %s, line %d',
                            $node['tag'],
                            $this->file,
                            $node['tag_pos'][0]
                        )
                    );
                }
                return $resultNodes;
            }
            if (isset($node['tag']) && !$node['closing'] && !$node['void']) {
                $openedTags[] = $node['tag'];
                $node['child_nodes'] = $this->reduce($nodes);
            }
            $resultNodes[] = $node;
        }

        return $resultNodes;
    }

    /**
     * Process template tag converting it into node structure with all tag info
     */
    public function processTag(int $startOffset): void
    {
        $this->offset = $startOffset;
        $this->switchState(Tag::class);
        while (!$this->currentState instanceof Stop && $this->nextChar()) {
            $this->currentState->process($this->getChar(), $this);
        }
        $this->nodeCounter++;
    }

    /**
     * Run specific state for further stream parsing.
     */
    public function switchState(string $stateName): void
    {
        if ($this->currentState) {
            $node = $this->currentState->getNode();
            match (get_class($this->currentState)) {
                Tag::class => $this->nodes[$this->nodeCounter] = [
                    'tag' => $node['buffer'],
                    'closing' => $node['closing'],
                    'tag_pos' => $this->getCharPosition(-strlen($node['buffer'])),
                ],
                TagEnd::class => $this->nodes[$this->nodeCounter]['void'] = $node['void'],
                TagAttr::class => $this->nodes[$this->nodeCounter]['attrs'][$node['buffer']] = null,
                TagAttrValue::class => $this->nodes[$this->nodeCounter]['attrs'][array_key_last(
                    $this->nodes[$this->nodeCounter]['attrs']
                )] = $node['buffer'],
                default => null
            };
        }

        $this->currentState = $this->getState($stateName);
        $this->currentState->clear();
        $this->currentState->process($this->getChar(), $this);
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
            $this->states[$stateClass] = new $stateClass();
        }
        return $this->states[$stateClass];
    }

    /**
     * Get current stream offset position
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * Get line and offset of currently processed steam character
     */
    public function getCharPosition(int $offsetCorrection = 0): array
    {
        $chunk = substr($this->stream, 0, $this->offset + $offsetCorrection);
        $line = substr_count($chunk, "\n") + 1;
        $lineOffset = $this->offset + $offsetCorrection - strrpos($chunk, "\n");
        return [$line, $lineOffset];
    }

    /**
     * Read next stream character by moving forward on input stream.
     */
    public function nextChar(): string
    {
        return $this->stream[++$this->offset] ?? '';
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
        return $this->stream[$this->offset];
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
     * Return the name of input stream file
     */
    public function getFile(): string
    {
        return $this->file;
    }
}
