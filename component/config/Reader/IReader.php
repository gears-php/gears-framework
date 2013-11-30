<?php
/**
 * @author deniskrasilnikov86@gmail.com
 */
namespace Gears\Config\Reader;

/**
 * Interface which should be implemented by any concrete configuration file reader
 * @package Gears\Config
 */
interface IReader
{
    /**
     * Parse the given configuration file and return configuration tree array
     * @param string $filename Configuration file full name
     * @return array Configuration tree
     */
    public function getFileConfig($filename);

    /**
     * Return configuration files extension
     * @return string
     */
    public function getFileExt();
}