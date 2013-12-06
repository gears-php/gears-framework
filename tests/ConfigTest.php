<?php

use Gears\Config\Config;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    protected $pathToProp = 'path.to.prop';
    protected $pathTo = 'path.to';
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

    /**
     * @depends testSet
     */
    public function testGetObj($config)
    {
        $subConfig = $config->getObj($this->pathTo);
        $this->assertInstanceOf('Gears\Config\Config', $subConfig);
        $actual = $this->readAttribute($subConfig, 'storage');
        $this->assertEquals($this->propStorage, $actual);
    }

    /**
     * @depends testSet
     */
    public function testGetFullConfig($config)
    {
        $this->assertEquals($this->storage, $config->get());
    }

    /**
     * @depends testSet
     */
    public function testGetFirstLevelValue($config)
    {
        $this->assertEquals($this->storage['path'], $config->path);
    }

    public function testGetFromExternal()
    {
        $config = new Config();
        $this->assertEquals($this->propStorage, $config->get($this->pathTo, $this->storage));
    }

    /**
     * @depends testSet
     */
    public function testOffsetExists($config)
    {
        $this->assertTrue(isset($config[$this->pathToProp]));
    }

    /**
     * @depends testSet
     */
    public function testOffsetGet($config)
    {
        $this->assertEquals($this->value, $config[$this->pathToProp]);
    }

    public function testOffsetSet()
    {
        $config = new Config();
        $config[$this->pathToProp] = $this->value;
        $actual = $this->readAttribute($config, 'storage');
        $this->assertEquals($this->storage, $actual);
    }

    /**
     * @depends testSet
     */
    public function testOffsetUnset($config)
    {
        unset($config[$this->pathTo]);
        $expected = $this->storage;
        unset($expected['path']['to']);
        $this->assertEquals($expected, $this->readAttribute($config, 'storage'));
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
    }
}