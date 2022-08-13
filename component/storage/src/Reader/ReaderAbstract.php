<?php
/**
 * @author denis.krasilnikov@gears.com
 */
namespace Gears\Storage\Reader;

use Gears\Storage\Reader\Exception\FileNotFound;

/**
 * Abstract reader which should be extended by any concrete data file reader
 *
 * @package Gears\Storage
 */
abstract class ReaderAbstract
{
    /**
     * Take the file path and return data tree array
     *
     * @throws FileNotFound
     */
    public function read(string $file): array
    {
        if (is_file($file)) {
            return $this->parseFile($file);
        } else {
            throw new FileNotFound($file);
        }
    }

    /**
     * Return data file extension
     */
    abstract public function getFileExt(): string;

    /**
     * Parse the given file and return data tree
     */
    abstract protected function parseFile(string $file): array;
}
