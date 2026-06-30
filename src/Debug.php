<?php
declare(strict_types=1);

namespace Gears\Framework;

class Debug
{
    private static array $buffer = [];
    private static bool $enabled = false;
    private static float $startTime = 0.0;
    private static float $endTime = 0.0;

    private function __construct() {}

    public static function enable(): void
    {
        self::$enabled = true;
        self::$startTime = microtime(true);
    }

    public static function enabled(): bool
    {
        return self::$enabled;
    }

    public static function timeLabel(string $message): void
    {
        if (self::$enabled) {
            self::$buffer[] = [
                'message' => $message,
                'time'    => microtime(true) - self::$startTime
            ];
        }
    }

    public static function scriptEnd(): void
    {
        if (self::$enabled && self::$endTime === 0.0) {
            self::$endTime = round((microtime(true) - self::$startTime) * 1000, 3);
        }
    }

    public static function dump(): string
    {
        if (!self::$enabled) return '';
        if (self::$endTime === 0.0) self::scriptEnd();

        $memUsage = self::getMemoryUsage();
        $isCli = (php_sapi_name() === 'cli');

        if ($isCli) {
            $cliOutput = "\n🚀 [GEARS TIMELINE]\n";
            foreach (self::$buffer as $marker) {
                $cliOutput .= sprintf("[+%s ms] %s\n", round($marker['time'] * 1000, 3), $marker['message']);
            }
            $cliOutput .= sprintf("⏱️ %s ms | 🧠 %s\n", self::$endTime, $memUsage);
            return $cliOutput;
        }

        $htmlOutput = '';
        foreach (self::$buffer as $marker) {
            $htmlOutput .= sprintf('<b>[+%s ms]</b> %s<br/>', round($marker['time'] * 1000, 3), htmlspecialchars($marker['message'], ENT_QUOTES, 'UTF-8'));
        }
        $htmlOutput .= sprintf('<hr style="border:0; border-top:1px solid #444; margin:10px 0;"/>⏱️ <b>Total:</b> %s ms | 🧠 <b>Memory:</b> %s', self::$endTime, $memUsage);

        return '<div style="background:#18171B; color:#FFF; padding:15px; font-family:monospace; border-left:5px solid #ff5555; line-height:1.5; font-size:13px;">' . $htmlOutput . '</div>';
    }

    public static function getMemoryUsage(): ?string
    {
        if (!self::$enabled) return null;
        $memUsage = memory_get_peak_usage(true);
        if ($memUsage < 1024) return $memUsage . ' bytes';
        if ($memUsage < 1048576) return round($memUsage / 1024, 2) . ' Kb';
        return round($memUsage / 1048576, 2) . ' Mb';
    }
}