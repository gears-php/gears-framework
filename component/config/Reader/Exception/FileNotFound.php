<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 12/7/13
 * Time: 12:58 PM
 */

namespace Gears\Config\Reader\Exception;

class FileNotFound extends \Exception
{
    public function __construct($file)
    {
        parent::__construct(sprintf('Config file not found at %s', $file));
    }
} 