<?php

namespace Gears\Config\Tests;

use Gears\Config\Config;
use Gears\Config\Reader\Yaml;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected $pathToProp = 'path.to.prop';
    protected $pathTo = 'path.to';
    protected $filePath = 'path/to/file';
    protected $value = 'value';
    protected $storage;
    protected $propStorage;

    public function setUp()
    {
        $this->propStorage = [
            'prop' => $this->value
        ];

        $this->storage = [
            'path' => [
                'to' => $this->propStorage
            ]
        ];
    }

    public function testSet()
    {
        $config = new Config();
        $config->set($this->pathToProp, $this->value);
        $actual = $this->readAttribute($config, 'storage');
        $this->assertEquals($this->storage, $actual);
        return $config;
    }

    /**
     * @depends testSet
     */
    public function testGet($config)
    {
        $this->assertEquals($this->value, $config->get($this->pathToProp));
    }

    public function testGetObj()
    {
        $config = $this->getMock(Config::class, array('get'));
        $config->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo($this->pathTo),
                $this->equalTo(null)
            );
        $this->assertInstanceOf(Config::class, $config->getObj($this->pathTo));
    }

    /**
     * @depends testSet
     */
    public function testGetFullConfig($config)
    {
        $this->assertEquals($this->storage, $config->get());
    }

    public function testGetAsAProperty()
    {
        $config = $this->getMock(Config::class, array('get'));
        $config->expects($this->once())
            ->method('get')
            ->with($this->equalTo('path'))
            ->will($this->returnValue($this->value));
        $this->assertEquals($this->value, $config->path);
    }

    public function testGetFromGivenStorage()
    {
        $config = new Config();
        $this->assertEquals($this->propStorage, $config->get($this->pathTo, $this->storage));
    }

    public function testDel()
    {
        $config = new Config($this->storage);
        unset($config[$this->pathTo]);
        $expected = $this->storage;
        unset($expected['path']['to']);
        $this->assertEquals($expected, $this->readAttribute($config, 'storage'));
    }

    /**
     * @depends testSet
     */
    public function testOffsetExists($config)
    {
        $this->assertTrue(isset($config[$this->pathToProp]));
    }

    public function testOffsetGet()
    {
        $config = $this->getMock(Config::class, array('get'));
        $config->expects($this->once())
            ->method('get')
            ->with($this->equalTo($this->pathToProp))
            ->will($this->returnValue($this->value));
        $this->assertEquals($this->value, $config[$this->pathToProp]);
    }

    public function testOffsetSet()
    {
        $config = $this->getMock(Config::class, array('set'));
        $config->expects($this->once())
            ->method('set')
            ->with(
                $this->equalTo($this->pathToProp),
                $this->equalTo($this->value)
            );
        $config[$this->pathToProp] = $this->value;
    }

    public function testOffsetUnset()
    {
        $config = $this->getMock(Config::class, array('del'));
        $config->expects($this->once())
            ->method('del')
            ->with($this->equalTo($this->pathTo));
        unset($config[$this->pathTo]);
    }

    public function testSetReader()
    {
        $config = new Config();
        $yamlReader = new Yaml;
        $config->setReader($yamlReader);
        $actual = $this->readAttribute($config, 'reader');
        $this->assertInstanceOf('Gears\Config\Reader\Yaml', $actual);

    }

    public function testGetReader()
    {
        $config = new Config();
        $this->assertInstanceOf(Yaml::class, $config->getReader());
    }

    public function testLoad()
    {
        $config = $this->getMock(Config::class, array('read', 'set'));

        $config->expects($this->exactly(2))
            ->method('read')
            ->with($this->equalTo($this->filePath))
            ->will($this->returnValue($this->storage));

        $config->expects($this->at(2))
            ->method('set')
            ->with(
                $this->equalTo($this->pathTo),
                $this->equalTo($this->storage)
            );

        $this->assertEquals($this->storage, $config->load($this->filePath));
        $this->assertEquals($this->storage, $config->load($this->filePath, $this->pathTo));
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

        $config = $this->getMock(Config::class, array('get'));
        $config->setReader($readerMock);

        $config->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo($this->pathTo),
                $this->equalTo($this->storage)
            )
            ->will($this->returnValue($this->propStorage));

        $this->assertEquals($this->propStorage, $config->read($this->filePath, $this->pathTo));
        $this->assertEquals([], $config->read($this->filePath));

    }

    public function testReadObj()
    {
        $config = $this->getMock(Config::class, array('read'));
        $config->expects($this->once())
            ->method('read')
            ->with(
                $this->equalTo($this->filePath),
                $this->equalTo(null)
            );
        $this->assertInstanceOf(Config::class, $config->readObj($this->filePath));
    }
}