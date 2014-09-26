<?php
namespace Gears\Config\Tests\Reader;
use Gears\Config\Reader\ReaderAbstract;

class ReaderAbstractTest extends \PHPUnit_Framework_TestCase
{
    public function testRead()
    {
        $reader = $this->getMockForAbstractClass(ReaderAbstract::class);
        $reader->expects($this->once())
            ->method('parseFile')
            ->will($this->returnValue(array()));
        $this->assertEquals(array(), $reader->read(__DIR__ . '/ReaderAbstractTest.yml'));
    }

    /**
     * @expectedException \Gears\Config\Reader\Exception\FileNotFound
     */
    public function testReadThrowsExceptionWhenFileNotFound()
    {
        $reader = $this->getMockForAbstractClass(ReaderAbstract::class);
        $reader->read('path/to/file');
    }
}