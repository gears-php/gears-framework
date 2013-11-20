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
    const __SCRIPTTIME__ = '__SCRIPTTIME__';

    /**
     * Patterns for preg replacements
     *
     * @var array
     */
    private static $_pattern = array(
        "/<br\s?\/>/", // <br /> tag
        "/<\/?\w+>/i" // other tags
    );

    /**
     * Replacement values for different types of debug info cleaning
     *
     * default    - no cleaning of debug info
     * clean    - clean for `console`. All html tags are stripped while <br /> is replaced with "\n"
     *
     * @var array
     */
    private static $_replacements = array(
        'default' => [],
        'clean' => array("\n", ""),
    );

    /**
     * All debug messages are stored here
     *
     * @var string
     */
    private static $_messages = '';

    /**
     * Determines whether debug is enabled or not
     *
     * @var boolean
     */
    private static $_enabled = false;

    /**
     * Time labels storage. Timelabels are used to find execution time of
     * some code chunk
     *
     * @var array
     */
    private static $_timeLabels = [];

    /**
     * Private constructor prevents direct instantinating
     */
    private function __construct()
    {
    }

    /**
     * Enable or disable debugging
     *
     * @param bool $bool
     */
    public static function enable($bool = true)
    {
        self::$_enabled = $bool;

        // if we are enabling debugging first time start capturing total script time
        if ($bool && !isset(self::$_timeLabels[self::__SCRIPTTIME__])) {
            self::timeStart(self::__SCRIPTTIME__);
        }
    }

    /**
     * Whether debug is enabled or not
     */
    public static function enabled()
    {
        return self::$_enabled;
    }

    /**
     * Adding some info to debug messages storage. Debugging should be
     * previously enabled otherwise storage is left empty
     */
    public static function add()
    {
        if (self::$_enabled) {
            $args = func_get_args();
            foreach ($args as $mixed) {
                self::$_messages .= htmlentities(addslashes(is_scalar($mixed) ? $mixed : var_export($mixed, true))) . '<br/>';
            }
        }
    }

    public static function get()
    {
        return self::_getMessages('default');
    }

    /**
     * Get clean (console) debug info output, meaning all html tags
     * are stripped while <br /> is replaced with "\n"
     */
    public static function getClean()
    {
        return self::_getMessages('clean');
    }

    /**
     * Record a new time label
     * @param string $label
     */
    public static function timeStart($label = 'default')
    {
        if (self::$_enabled) {
            self::$_timeLabels[$label] = microtime(1);
            return self::$_timeLabels[$label];
        }
    }

    /**
     * Close a given time label, calculate and return the difference.
     * @param string $label
     * @return mixed
     */
    public static function timeEnd($label = 'default')
    {
        if (self::$_enabled) {
            $time = microtime(1) - self::$_timeLabels[$label];
            unset(self::$_timeLabels[$label]);
            return $time;
        }
    }

    /**
     * End time label and add the time value to debug
     * @param string $label
     */
    public static function timeAdd($label = 'default')
    {
        self::add($label . "\n" . self::timeEnd($label));
    }

    /**
     * Get script memory usage
     */
    public static function getMemoryUsage()
    {
        $mem_usage = memory_get_usage(true);

        if ($mem_usage < 1024) {
            $mem_usage .= ' bytes';
        } elseif ($mem_usage < 1048576) {
            $mem_usage = round($mem_usage / 1024, 2) . ' Kb';
        } else {
            $mem_usage = round($mem_usage / 1048576, 2) . ' Mb';
        }

        return $mem_usage;
    }

    /**
     * Get script execution time
     *
     * @return integer
     */
    public static function scriptTime()
    {
        return self::timeEnd(self::__SCRIPTTIME__);
    }

    /**
     * Clean given string from html characters while <br />
     * is replaced with "\n"
     */
    public static function clean($str)
    {
        return preg_replace(self::$_pattern, self::$_replacements['clean'], $str);
    }

    /**
     * Get all debug messages
     */
    private static function _getMessages($replacement)
    {
        if (self::$_enabled) {
            if (!empty(self::$_replacements[$replacement])) {
                $messages = preg_replace(self::$_pattern, self::$_replacements[$replacement], self::$_messages);

            } else $messages = self::$_messages;

            self::$_messages = '';
            return $messages;
        }
    }
}