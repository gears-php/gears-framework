<?php
/**
 * @author deniskrasilnikov86@gmail.com
 */
namespace Gears\Config\Reader;

/**
 * YAML markup files reader. Uses Spyc library for configuration files parsing
 * @package Gears\Config\Reader
 */
class Yaml extends ReaderAbstract
{
    public function parseFile($file)
    {
        return \Spyc::YAMLLoad($file);
    }

    public function getFileExt()
    {
        return '.yml';
    }
}
