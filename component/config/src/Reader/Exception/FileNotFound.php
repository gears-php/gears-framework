<?php
namespace Gears\Config\Reader\Exception;

class FileNotFound extends \Exception
{
    public function __construct($file)
    {
        parent::__construct(sprintf('Config file not found at %s', $file));
    }
} 