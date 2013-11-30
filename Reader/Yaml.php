<?php
/**
 * @author deniskrasilnikov86@gmail.com
 */
namespace Gears\Config\Reader;

/**
 * YAML markup files reader. Uses Spyc library for configuration files parsing
 * @package Gears\Config\Reader
 */
class Yaml implements IReader
{
    private $yamlFileExtension = '.yml';

    public function getFileConfig($filename)
    {
        return \Spyc::YAMLLoad($filename);
    }

    public function getFileExt()
    {
        return $this->yamlFileExtension;
    }
}
