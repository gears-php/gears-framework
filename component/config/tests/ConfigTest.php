<?php

use Gears\Config\Config;

class ConfigTest extends PHPUnit_Framework_TestCase
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
        $config = $this->getMock('Gears\Config\Config', array('get'));
        $config->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo($this->pathTo),
                $this->equalTo(null)
            );
        $this->assertInstanceOf('Gears\Config\Config', $config->getObj($this->pathTo));
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
        $config = $this->getMock('Gears\Config\Config', array('get'));
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
        $config = $this->getMock('Gears\Config\Config', array('get'));
        $config->expects($this->once())
            ->method('get')
            ->with($this->equalTo($this->pathToProp))
            ->will($this->returnValue($this->value));
        $this->assertEquals($this->value, $config[$this->pathToProp]);
    }

    public function testOffsetSet()
    {
        $config = $this->getMock('Gears\Config\Config', array('set'));
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
        $config = $this->getMock('Gears\Config\Config', array('del'));
        $config->expects($this->once())
            ->method('del')
            ->with($this->equalTo($this->pathTo));
        unset($config[$this->pathTo]);
    }

    public function testSetReader()
    {
        $config = new Config();
        $yamlReader = new Gears\Config\Reader\Yaml;
        $config->setReader($yamlReader);
        $actual = $this->readAttribute($config, 'reader');
        $this->assertInstanceOf('Gears\Config\Reader\Yaml', $actual);

    }

    public function testGetReader()
    {
        $config = new Config();
        $this->assertInstanceOf('Gears\Config\Reader\Yaml', $config->getReader());
    }

    public function testLoad()
    {
    }

    public function testRead()
    {
    }

    public function testReadObj()
    {
        $config = $this->getMock('Gears\Config\Config', array('read'));
        $config->expects($this->once())
            ->method('read')
            ->with(
                $this->equalTo($this->filePath),
                $this->equalTo(null)
            );
        $this->assertInstanceOf('Gears\Config\Config', $config->readObj($this->filePath));
    }
}