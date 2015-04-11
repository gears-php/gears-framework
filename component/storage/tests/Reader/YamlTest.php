<?php

namespace Gears\Storage\Tests\Reader;

use Gears\Storage\Reader\Yaml;

class YamlTest extends \PHPUnit_Framework_TestCase
{
    public function testGetFileExt()
    {
        $yaml = new Yaml();
        $this->assertEquals('.yml', $yaml->getFileExt());
    }

    public function testParseFile()
    {
        $yaml = new Yaml();
        $parseFile = new \ReflectionMethod($yaml, 'parseFile');
        $parseFile->setAccessible(true);
        require 'Spyc.php'; // Spyc stub
        $this->assertEquals([], $parseFile->invoke($yaml, $file = 'path/to/file'));
        $this->assertEquals($file, \Spyc::getLoadedFilename());
    }
}
