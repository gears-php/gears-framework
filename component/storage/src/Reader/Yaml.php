<?php
/**
 * @author deniskrasilnikov86@gmail.com
 */
namespace Gears\Storage\Reader;

/**
 * YAML markup files reader. Uses Spyc library for configuration files parsing
 * @package Gears\Storage\Reader
 */
class Yaml extends ReaderAbstract
{
    public function getFileExt()
    {
        return '.yml';
    }

    protected function parseFile($file)
    {
        return \Spyc::YAMLLoad($file);
    }
}
