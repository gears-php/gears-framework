<?php

/**
 * The `stub` used for real Spyc library replacement during the Yaml reader testing
 */
class Spyc
{
    protected static $loadedFilename;

    public static function YAMLLoad($file)
    {
        if (!empty($file)) {
            self::$loadedFilename = $file;
            return [];
        }
    }

    public static function getLoadedFilename()
    {
        return self::$loadedFilename;
    }
}
