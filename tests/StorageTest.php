<?php

namespace Gears\Storage\Tests;

use Gears\Storage\Storage;
use Gears\Storage\Reader\Yaml;

class StorageTest extends \PHPUnit_Framework_TestCase
{
    protected $pathToValue = 'path.to.value';
    protected $pathTo = 'path.to';
    protected $filePath = 'path/to/file';
    protected $value = 'value';
    protected $storage;
    protected $valueStorage;

    public function setUp()
    {
        $this->valueStorage = [
            'value' => $this->value
        ];

        $this->storage = [
            'path' => [
                'to' => $this->valueStorage
            ]
        ];
    }

    public function testSet()
    {
        $storage = new Storage();
        $storage->set($this->pathToValue, $this->value);
        $actual = $this->readAttribute($storage, 'storage');
        $this->assertEquals($this->storage, $actual);
        return $storage;
    }

    /**
     * @depends testSet
     */
    public function testGet($storage)
    {
        $this->assertEquals($this->value, $storage->get($this->pathToValue));
    }

    public function testGetObj()
    {
        $storage = $this->getMock(Storage::class, ['get']);
        $storage->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo($this->pathTo),
                $this->equalTo(null)
            );
        $this->assertInstanceOf(Storage::class, $storage->getObj($this->pathTo));
    }

    /**
     * @depends testSet
     */
    public function testGetFullStorage($storage)
    {
        $this->assertEquals($this->storage, $storage->get());
    }

    public function testGetAsAProperty()
    {
        $storage = $this->getMock(Storage::class, ['get']);
        $storage->expects($this->once())
            ->method('get')
            ->with($this->equalTo('path'))
            ->will($this->returnValue($this->value));
        $this->assertEquals($this->value, $storage->path);
    }

    public function testGetFromGivenStorage()
    {
        $storage = new Storage();
        $this->assertEquals($this->valueStorage, $storage->get($this->pathTo, $this->storage));
    }

    public function testDel()
    {
        $storage = new Storage($this->storage);
        unset($storage[$this->pathTo]);
        $expected = $this->storage;
        unset($expected['path']['to']);
        $this->assertEquals($expected, $this->readAttribute($storage, 'storage'));
    }

    /**
     * @depends testSet
     */
    public function testOffsetExists($storage)
    {
        $this->assertTrue(isset($storage[$this->pathToValue]));
    }

    public function testOffsetGet()
    {
        $storage = $this->getMock(Storage::class, ['get']);
        $storage->expects($this->once())
            ->method('get')
            ->with($this->equalTo($this->pathToValue))
            ->will($this->returnValue($this->value));
        $this->assertEquals($this->value, $storage[$this->pathToValue]);
    }

    public function testOffsetSet()
    {
        $storage = $this->getMock(Storage::class, ['set']);
        $storage->expects($this->once())
            ->method('set')
            ->with(
                $this->equalTo($this->pathToValue),
                $this->equalTo($this->value)
            );
        $storage[$this->pathToValue] = $this->value;
    }

    public function testOffsetUnset()
    {
        $storage = $this->getMock(Storage::class, ['del']);
        $storage->expects($this->once())
            ->method('del')
            ->with($this->equalTo($this->pathTo));
        unset($storage[$this->pathTo]);
    }

    public function testSetReader()
    {
        $storage = new Storage();
        $yamlReader = new Yaml;
        $storage->setReader($yamlReader);
        $actual = $this->readAttribute($storage, 'reader');
        $this->assertInstanceOf('Gears\Storage\Reader\Yaml', $actual);

    }

    public function testGetReader()
    {
        $storage = new Storage();
        $this->assertInstanceOf(Yaml::class, $storage->getReader());
    }

    public function testLoad()
    {
        $storage = $this->getMock(Storage::class, ['read', 'set']);

        $storage->expects($this->exactly(2))
            ->method('read')
            ->with($this->equalTo($this->filePath))
            ->will($this->returnValue($this->storage));

        $storage->expects($this->at(2))
            ->method('set')
            ->with(
                $this->equalTo($this->pathTo),
                $this->equalTo($this->storage)
            );

        $this->assertEquals($this->storage, $storage->load($this->filePath));
        $this->assertEquals($this->storage, $storage->load($this->filePath, $this->pathTo));
    }

    public function testRead()
    {
        $readerMock = $this->getMock(Yaml::class);
        $readerMock->expects($this->at(0))
            ->method('read')
            ->with($this->equalTo($this->filePath))
            ->will($this->returnValue($this->storage));

        $readerMock->expects($this->at(2))
            ->method('read')
            ->with($this->equalTo($this->filePath))
            ->will($this->returnValue([]));

        $readerMock->expects($this->exactly(2))
            ->method('getFileExt')
            ->will($this->returnValue('.yml'));

        $storage = $this->getMock(Storage::class, ['get']);
        $storage->setReader($readerMock);

        $storage->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo($this->pathTo),
                $this->equalTo($this->storage)
            )
            ->will($this->returnValue($this->valueStorage));

        $this->assertEquals($this->valueStorage, $storage->read($this->filePath, $this->pathTo));
        $this->assertEquals([], $storage->read($this->filePath));

    }

    public function testReadObj()
    {
        $storage = $this->getMock(Storage::class, ['read']);
        $storage->expects($this->once())
            ->method('read')
            ->with(
                $this->equalTo($this->filePath),
                $this->equalTo(null)
            );
        $this->assertInstanceOf(Storage::class, $storage->readObj($this->filePath));
    }
}
