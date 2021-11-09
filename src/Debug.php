<?php
/**
 * @copyright For the full copyright and license information, please view the LICENSE files included in this source code.
 */
declare(strict_types=1);

namespace Gears\Framework;

/**
 * @package    Gears\Framework
 * @subpackage Debug
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
class Debug
{
    private const SCRIPT_LABEL = '__SCRIPT TIME__';

    /**
     * Patterns for preg replacements
     */
    private static array $pattern = [
        "/<br\s?\/>/", // <br /> tag
        "/<\/?\w+>/i" // other tags
    ];

    /**
     * Replacement values for different types of debug info cleaning
     *
     * default - no cleaning of debug info
     * clean   - clean for `console`. All html tags are stripped and <br /> is replaced with "\n"
     */
    private static array $replacements = [
        'default' => [],
        'console' => ["\n", ""],
    ];

    /**
     * Stores all debug info
     */
    private static string $buffer = '';

    /**
     * Determines whether debug is enabled or not
     */
    private static bool $enabled = false;

    /**
     * Time labels storage. Time-labels are used to find execution time of some code chunk
     */
    private static array $timeLabels = [];

    private function __construct()
    {
    }

    /**
     * Enable debugging
     */
    public static function enable(): void
    {
        self::$enabled = true;
        self::timeStart(self::SCRIPT_LABEL);
    }

    /**
     * Whether debug is enabled or not
     */
    public static function enabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Adding some info to debug messages storage. Debugging should be
     * previously enabled otherwise storage is left empty
     */
    public static function add(): void
    {
        if (self::$enabled) {
            $args = func_get_args();
            foreach ($args as $mixed) {
                self::$buffer .= htmlentities(addslashes(is_scalar($mixed) ? $mixed : var_export($mixed, true))) . '<br/>';
            }
        }
    }

    public static function get(): string
    {
        if (!self::$enabled) {
            return '';
        }

        $type = php_sapi_name() !== 'cli' ? 'default' : 'console';

        if (!empty(self::$replacements[$type])) {
            $messages = preg_replace(self::$pattern, self::$replacements[$type], self::$buffer);
        } else {
            $messages = self::$buffer;
        }

        self::$buffer = $messages;

        return self::$buffer = '';
    }

    /**
     * Record a new time label for time difference calculation.
     */
    public static function timeStart(string $label): ?float
    {
        if (self::$enabled && !isset(self::$timeLabels[$label])) {
            self::$timeLabels[$label] = microtime(true);

            return self::$timeLabels[$label];
        }

        return null;
    }

    /**
     * Close a given time label, add resulting time diff to debug info and return it.
     */
    public static function timeEnd(string $label): ?float
    {
        if (!self::$enabled) {
            return 0;
        }

        $time = microtime(true) - self::$timeLabels[$label];
        unset(self::$timeLabels[$label]);

        self::add($label . "\n" . $time);

        return $time;
    }

    /**
     * Get script memory usage
     */
    public static function getMemoryUsage(): ?string
    {
        if (!self::$enabled) {
            return null;
        }

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
        return round(self::timeEnd(self::SCRIPT_LABEL), 3);
    }
}