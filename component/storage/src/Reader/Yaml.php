<?php
/**
 * @author denis.krasilnikov@gears.com
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
    public function getFileExt(): string
    {
        return '.yaml';
    }

    /**
     * {@inheritdoc}
     */
    protected function parseFile(string $file): array
    {
        return \Spyc::YAMLLoad($file);
    }
}
