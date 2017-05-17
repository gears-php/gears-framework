<?php
namespace Gears\Storage\Reader\Exception;

/**
 * Exception thrown in case the file with storage data can not be found
 *
 * @package Gears\Storage\Reader\Exception
 */
class FileNotFound extends \Exception
{
    public function __construct($file)
    {
        parent::__construct(sprintf('Data file not found at %s', $file));
    }
} 
