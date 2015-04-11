<?php
namespace Gears\Storage\Tests\Reader;
use Gears\Storage\Reader\ReaderAbstract;

class ReaderAbstractTest extends \PHPUnit_Framework_TestCase
{
    public function testRead()
    {
        $reader = $this->getMockForAbstractClass(ReaderAbstract::class);
        $reader->expects($this->once())
            ->method('parseFile')
            ->will($this->returnValue([]));
        $this->assertEquals([], $reader->read(__DIR__ . '/ReaderAbstractTest.yml'));
    }

    /**
     * @expectedException \Gears\Storage\Reader\Exception\FileNotFound
     */
    public function testReadThrowsExceptionWhenFileNotFound()
    {
        $reader = $this->getMockForAbstractClass(ReaderAbstract::class);
        $reader->read('path/to/file');
    }
}
