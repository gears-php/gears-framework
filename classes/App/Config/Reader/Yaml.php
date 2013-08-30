<?php
/**
 * @author deniskrasilnikov86@gmail.com
 */

namespace Gears\Framework\App\Config\Reader;

/**
 * YAML markup files reader. Uses Spyc library thus it mast be already deployed
 * and included into your project (as a composer package for example)
 * @package Gears\Framework\App\Config\Reader
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