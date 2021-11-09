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
     * @param string $file Data file full name
     *
     * @return array Storage data tree
     *
     * @throws FileNotFound
     */
    public function read($file)
    {
        if (is_file($file)) {
            return $this->parseFile($file);
        } else {
            throw new FileNotFound($file);
        }
    }

    /**
     * Return data file extension
     *
     * @return string
     */
    abstract public function getFileExt();

    /**
     * Parse the given file and return data tree
     *
     * @param string $file Data file full name
     *
     * @return array Storage data tree
     */
    abstract protected function parseFile($file);
}
