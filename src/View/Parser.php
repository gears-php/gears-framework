<?php

/**
 * @author: Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
declare(strict_types=1);

namespace Gears\Framework\View;

use Gears\Framework\View\Exception\TemplateSyntaxException;
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
     * Current stream character byte offset
     */
    private int $offset = 0;

    /**
     * Byte offsets of every line start
     */
    private array $lineOffsets = [];

    /**
     * Current line number (1-based)
     */
    private int $line = 1;

    /**
     * Current column number in Unicode characters (1-based)
     */
    private int $column = 1;

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
        $this->buildLineOffsets();

        // capture all template tags start positions
        preg_match_all(
            sprintf('/<!--.*?-->(*SKIP)(*F)|<\/?(?:%s)[ >]/s', implode('|', $this->tags)),
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

    /** Reduce plain array of nodes into nested structure */
    public function reduce(array &$nodes, int &$index = 0): array
    {
        static $openedTags = [];
        $resultNodes = [];
        $count = count($nodes);

        while ($index < $count) {
            $node = $nodes[$index++];
            if (isset($node['tag']) && $node['closing']) {
                $oTag = array_pop($openedTags);
                if ($oTag != $node['tag']) {
                    throw new TemplateSyntaxException(
                        sprintf('Missing opening tag for &lt;/%s&gt; tag', $node['tag']),
                        $this->file,
                        $node['tag_pos'][0],
                        $node['tag_pos'][1] - 1

                    );
                }
                return $resultNodes;
            }
            if (isset($node['tag']) && !$node['closing'] && !$node['void']) {
                $openedTags[] = $node['tag'];
                $node['child_nodes'] = $this->reduce($nodes, $index);
            }
            $resultNodes[] = $node;
        }

        return $resultNodes;
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
                    'tag_pos' => $this->getCharPosition(-strlen($node['buffer']) - 1),
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
     * Get line and offset of currently processed steam character
     */
    public function getCharPosition(int $offsetCorrection = 0): array
    {
        $pos = $this->offset + $offsetCorrection;
        $left = 0;
        $right = count($this->lineOffsets) - 1;

        while ($left <= $right) {
            $middle = intdiv($left + $right, 2);

            if ($this->lineOffsets[$middle] <= $pos) {
                $left = $middle + 1;
            } else {
                $right = $middle - 1;
            }
        }

        $line = $right + 1;
        $lineStart = $this->lineOffsets[$right];
        $column = mb_strlen(
                substr($this->stream, $lineStart, $pos - $lineStart),
                'UTF-8'
            ) + 1;

        return [$line, $column];
    }

    /**
     * Read next stream character by moving forward on input stream.
     */
    public function nextChar(): string
    {
        return $this->stream[++$this->offset] ?? '';
    }

    /**
     * Get current stream character
     */
    public function getChar(): string
    {
        return $this->stream[$this->offset];
    }

    /**
     * Match given character(s) with the ones at specific relative offset
     * @param string $chars Character or character string
     * @param int $offset (optional) Offset value relative to the current inner offset
     * @return bool
     */
    public function isChar(string $chars, int $offset = 0): bool
    {
        $len = strlen($chars);
        $start = $this->offset + $offset;

        for ($i = 0; $i < $len; $i++) {
            $pos = $start + $i;
            if (!isset($this->stream[$pos]) || $this->stream[$pos] !== $chars[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return the name of input stream file
     */
    public function getFile(): string
    {
        return $this->file;
    }

    private function buildLineOffsets(): void
    {
        $this->lineOffsets = [0];

        $length = strlen($this->stream);

        for ($i = 0; $i < $length; $i++) {
            if ($this->stream[$i] === "\n") {
                $this->lineOffsets[] = $i + 1;
            }
        }
    }

    /**
     * Process template tag converting it into node structure with all tag info
     */
    private function processTag(int $startOffset): void
    {
        $this->offset = $startOffset;
        $this->switchState(Tag::class);
        while (!$this->currentState instanceof Stop && $this->nextChar()) {
            $this->currentState->process($this->getChar(), $this);
        }
        $this->nodeCounter++;
    }
}
