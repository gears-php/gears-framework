<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */

namespace Gears\Framework;

/**
 * Debug
 *
 * @package    Gears\Framework
 * @subpackage Debug
 */
class Debug
{
    private const SCRIPT_LABLE = '__SCRIPTTIME__';

    /**
     * Patterns for preg replacements
     */
    private static array $pattern = array(
        "/<br\s?\/>/", // <br /> tag
        "/<\/?\w+>/i" // other tags
    );

    /**
     * Replacement values for different types of debug info cleaning
     *
     * default    - no cleaning of debug info
     * clean    - clean for `console`. All html tags are stripped while <br /> is replaced with "\n"
     */
    private static array $replacements = array(
        'default' => [],
        'clean' => array("\n", ""),
    );

    /**
     * All debug messages are stored here
     */
    private static string $messages = '';

    /**
     * Determines whether debug is enabled or not
     */
    private static bool $enabled = false;

    /**
     * Time labels storage. Timelabels are used to find execution time of
     * some code chunk
     */
    private static array $timeLabels = [];

    /**
     * Private constructor prevents direct instantinating
     */
    private function __construct()
    {
    }

    /**
     * Enable debugging
     */
    public static function enable()
    {
        self::$enabled = true;
        self::timeStart(self::SCRIPT_LABLE);
    }

    /**
     * Disable debugging
     */
    public static function disable()
    {
        self::$enabled = false;
    }

    /**
     * Whether debug is enabled or not
     */
    public static function enabled()
    {
        return self::$enabled;
    }

    /**
     * Adding some info to debug messages storage. Debugging should be
     * previously enabled otherwise storage is left empty
     */
    public static function add()
    {
        if (self::$enabled) {
            $args = func_get_args();
            foreach ($args as $mixed) {
                self::$message .= htmlentities(addslashes(is_scalar($mixed) ? $mixed : var_export($mixed, true))) . '<br/>';
            }
        }
    }

    public static function get()
    {
        return self::getMessages('default');
    }

    /**
     * Get clean (console) debug info output, meaning all html tags
     * are stripped while <br /> is replaced with "\n"
     */
    public static function getClean()
    {
        return self::getMessages('clean');
    }

    /**
     * Record a new time label
     */
    public static function timeStart(string $label)
    {
        if (self::$enabled && !isset(self::$timeLabels[$label])) {
            self::$timeLabels[$label] = microtime(1);

            return self::$timeLabels[$label];
        }
    }

    /**
     * Close a given time label, calculate and return the time difference.
     */
    public static function timeEnd(string $label)
    {
        if (self::$enabled) {
            $time = microtime(1) - self::$timeLabels[$label];
            unset(self::$timeLabels[$label]);

            return $time;
        }
    }

    /**
     * End time label and add the time value to debug
     * @param string $label
     */
    public static function timeAdd($label)
    {
        self::add($label . "\n" . self::timeEnd($label));
    }

    /**
     * Get script memory usage
     */
    public static function getMemoryUsage()
    {
        $memUsage = memory_get_usage(true);

        if ($memUsage < 1024) {
            $memUsage .= ' bytes';
        } elseif ($memUsage < 1048576) {
            $memUsage = round($memUsage / 1024, 2) . ' Kb';
        } else {
            $memUsage = round($memUsage / 1048576, 2) . ' Mb';
        }

        return $memUsage;
    }

    /**
     * Get script execution time
     */
    public static function scriptTime(): float
    {
        return round(self::timeEnd(self::SCRIPT_LABLE), 3);
    }

    /**
     * Clean given string from html characters while <br />
     * is replaced with "\n"
     */
    public static function clean($str)
    {
        return preg_replace(self::$pattern, self::$replacements['clean'], $str);
    }

    /**
     * Get all debug messages
     */
    private static function getMessages($replacement)
    {
        if (self::$enabled) {
            if (!empty(self::$replacements[$replacement])) {
                $messages = preg_replace(self::$pattern, self::$replacements[$replacement], self::$message);
            } else {
                $messages = self::$message;
            }

            self::$message = '';

            return $messages;
        }
    }
}