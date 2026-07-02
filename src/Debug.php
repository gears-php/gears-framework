<?php

declare(strict_types=1);

namespace Gears\Framework {

    class Debug
    {
        private static array $buffer = [];
        private static bool $enabled = false;

        private function __construct()
        {
        }

        public static function enable(): void
        {
            self::$enabled = true;
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
                    'time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
                ];
            }
        }

        public static function getDebugBar(array $sqlQueries = []): string
        {
            if (!self::$enabled) {
                return '';
            }
            // script total execution time
            $scriptTimeMs = (int)round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000);
            // script peak memory usage
            $memUsageMb = round(memory_get_peak_usage() / 1048576, 2);

            $sqlHtml = '';
            if ($sqlQueries) {
                $sqlHtml .= '<div id="gears-queries-popup" style="display: none; border-bottom: 1px solid #dee2e6; margin: 6px 0 6px; padding-bottom: 6px; max-height: 150px; overflow-y: auto; font-size: 12px; text-align: left;">';
                foreach ($sqlQueries as $sql) {
                    $sqlHtml .= sprintf(
                        '<div style="margin-bottom: 3px; line-height: 1.6;"><span style="color: darkgray; margin-right: 5px;">%.1f ms</span>&nbsp;%s</div>',
                        $sql['time'],
                        htmlspecialchars($sql['raw'])
                    );
                }
                $sqlHtml .= '</div>';
            }

            $toggleQueriesJs = "const p = document.getElementById('gears-queries-popup'); if(p) p.style.display = p.style.display === 'none' ? 'block' : 'none'; event.stopPropagation();";

            return sprintf(
                '<div id="gears-debug-bar" style="position: fixed; bottom: 12px; right: 16px; background-color: #fff; color: #212529; font-family: SFMono-Regular, Menlo, Monaco, Consolas, \'Liberation Mono\', \'Courier New\', monospace; font-size: 13px; padding: 6px 12px; box-shadow: rgba(0, 0, 0, 0.15) 0px 8px 16px 0px; border: 1px solid #dee2e6; border-radius: .375rem; z-index: 999999; max-width: 65%%; pointer-events: auto; display: inline-block; text-align: right;">
                    %s
                    <div style="display: flex; justify-content: flex-end; gap: 16px; font-weight: 500;">
                        <span title="Script execution time" style="display: flex; align-items: center; gap: 4px;">
                            <span style="font-size: 16px;">◷</span><span>%d ms</span>
                        </span>
                        <span title="Memory usage" style="display: flex; align-items: center; gap: 4px;">
                            <span style="font-size: 16px;">▤</span><span>%2.2f Mb</span>
                        </span>
                       <span onclick="%s" style="cursor: pointer; display: flex; align-items: center; gap: 4px;">Queries <span style="background: #e9ecef; padding: 1px 5px; border-radius: 3px; font-weight: bold; font-size: 11px;">%d</span></span>
                    </div>
                </div>',
                $sqlHtml,
                $scriptTimeMs,
                $memUsageMb,
                $toggleQueriesJs,
                count($sqlQueries)
            );
        }
    }
}

namespace Gears\Framework\Debug\Helper {

    use JetBrains\PhpStorm\NoReturn;

    /**
     * Pretty variables dump WITHOUT script termination
     */
    function Dump(...$vars): void
    {
        $isCli = (php_sapi_name() === 'cli');

        // One Light theme scheme configurations
        $styles = [
            T_VARIABLE => 'color: #e45649;', // variables
            T_STRING => 'color: #4078f2;', // constructs (stdClass, etc.)
            T_CONSTANT_ENCAPSED_STRING => 'color: #50a14f;', // string values
            T_LNUMBER => 'color: #886801;', // integers
            T_DNUMBER => 'color: #886801;', // floats
            T_COMMENT => 'color: #a0a1a7; font-style: italic;', // comments
            T_DOC_COMMENT => 'color: #a0a1a7; font-style: italic;',
        ];

        $arrow_style = 'color: #a626a4; font-weight: bold;'; // purple for =>
        $key_style = 'color: #b76b00; font-weight: 500;'; // warm brown for array keys
        $char_style = 'color: #383a42;'; // slate dark grey for brackets/commas
        $badge_style = 'color: #a626a4; font-weight: bold; font-size: 11px;'; // purple style for array:N badge

        foreach ($vars as $var) {
            $output = var_export($var, true);

            if ($isCli) {
                echo "\n💡 [GEARS DUMP]:\n" . $output . "\n";
                continue;
            }

            if (is_array($var)) {
                $output = 'array:' . count($var) . substr($output, 5);
            }

            $tokens = token_get_all("<?php " . $output);
            $compiledTokens = [];
            $tokenCount = 0;

            foreach (array_filter($tokens, fn($t) => !is_array($t) || $t[0] !== T_OPEN_TAG) as $token) {
                if (is_array($token)) {
                    $compiledTokens[$tokenCount++] = [
                        'type' => $token[0],
                        'text' => '<span style="' . ($styles[$token[0]] ?? $badge_style) . '">' . htmlspecialchars($token[1]) . '</span>'
                    ];
                    continue;
                }

                $text = match ($token) {
                    '(' => '[',
                    ')' => ']',
                    default => $token
                };

                $compiledTokens[$tokenCount++] = [
                    'type' => ($token === '=>' ? T_DOUBLE_ARROW : 'CHAR'),
                    'text' => '<span style="' . ($token === '=>' ? $arrow_style : $char_style) . '">' . htmlspecialchars($text) . '</span>'
                ];
            }

            for ($i = 0; $i < $tokenCount; $i++) {
                if ($compiledTokens[$i]['type'] !== T_DOUBLE_ARROW) {
                    continue;
                }

                $prev = $i - 1;
                while ($prev >= 0 && $compiledTokens[$prev]['type'] === T_WHITESPACE) {
                    $prev--;
                }

                $allowedTypes = [T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER, T_STRING];
                if ($prev >= 0 && in_array($compiledTokens[$prev]['type'], $allowedTypes, true)) {
                    if (preg_match('/<span[^>]*>(.*)<\/span>/s', $compiledTokens[$prev]['text'], $match)) {
                        $compiledTokens[$prev]['text'] = '<span style="' . $key_style . '">' . $match[1] . '</span>';
                    }
                }
            }

            echo '<pre style="background: #fdfdfd; border: 1px solid #e9ecef; padding: 8px 12px; font-family: monospace; font-size: 12px; margin: 8px 0; overflow-x: auto; border-radius: 4px; line-height: 1.4;">';
            echo implode('', array_column($compiledTokens, 'text'));
            echo '</pre>';
        }
    }

    /**
     * Dump and Die
     */
    #[NoReturn] function Dd(...$vars): void
    {
        Dump(...$vars);
        die();
    }
}
