<?php

declare(strict_types=1);

use Gears\Framework\Debug;

if (!function_exists('dump')) {
    /**
     * Pretty variables dump WITHOUT script termination
     */
    function dump(...$vars): void
    {
        $isCli = (php_sapi_name() === 'cli');

        foreach ($vars as $var) {
            $output = var_export($var, true);

            if ($isCli) {
                echo "\n💡 [GEARS DUMP]:\n" . $output . "\n";
            } else {
                echo '<pre style="background:#18171B; color:#569CD6; padding:15px; font-family:monospace; font-size:14px; border-radius:4px; margin:10px 0; overflow-x:auto; line-height:1.4;">';

                $highlighted = highlight_string("<?php\n" . $output, true);
                echo str_replace('&lt;?php<br />', '', $highlighted);

                echo '</pre>';
            }
        }
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and Die
     */
    function dd(...$vars): void
    {
        dump(...$vars);
        Debug::scriptEnd();
        die();
    }
}