<?php
/**
 * @author deniskrasilnikov86@gmail.com
 */
namespace Gears\Storage\Reader;
use Gears\Storage\Reader\Exception\FileNotFound;

/**
 * Abstract reader which should be extended by any concrete configuration file reader
 * @package Gears\Storage
 */
abstract class ReaderAbstract
{
    /**
     * Take the file path and return configuration tree array
     * @param string $file Configuration file full name
     * @return array Configuration tree
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
     * Return configuration file extension
     * @return string
     */
    abstract public function getFileExt();

    /**
     * Parse the given file and return configuration tree
     * @param string $file Configuration file full name
     * @return array Configuration tree
     */
    abstract protected function parseFile($file);
}
