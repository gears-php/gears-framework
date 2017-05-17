<?php
/**
 * @author deniskrasilnikov86@gmail.com
 */
namespace Gears\Storage\Reader;

/**
 * YAML files reader. Uses Spyc library for data files parsing
 *
 * @package Gears\Storage\Reader
 */
class Yaml extends ReaderAbstract
{
    /**
     * {@inheritdoc}
     */
    public function getFileExt()
    {
        return '.yml';
    }

    /**
     * {@inheritdoc}
     */
    protected function parseFile($file)
    {
        return \Spyc::YAMLLoad($file);
    }
}
