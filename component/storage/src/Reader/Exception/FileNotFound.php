<?php
namespace Gears\Storage\Reader\Exception;

class FileNotFound extends \Exception
{
    public function __construct($file)
    {
        parent::__construct(sprintf('Data file not found at %s', $file));
    }
} 
