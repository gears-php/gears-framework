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

        // One Light theme scheme configurations
        $styles = [
            T_VARIABLE                 => 'color: #e45649;', // variables
            T_STRING                   => 'color: #4078f2;', // constructs (stdClass, etc.)
            T_CONSTANT_ENCAPSED_STRING => 'color: #50a14f;', // string values
            T_LNUMBER                  => 'color: #886801;', // integers
            T_DNUMBER                  => 'color: #886801;', // floats
            T_COMMENT                  => 'color: #a0a1a7; font-style: italic;', // comments
            T_DOC_COMMENT              => 'color: #a0a1a7; font-style: italic;',
        ];

        $arrow_style = 'color: #a626a4; font-weight: bold;'; // purple for =>
        $key_style   = 'color: #b76b00; font-weight: 500;'; // warm brown for array keys
        $char_style  = 'color: #383a42;'; // slate dark grey for brackets/commas
        $badge_style = 'color: #a626a4; font-weight: bold; font-size: 11px;'; // purple style for array:N badge

        foreach ($vars as $var) {
            $output = var_export($var, true);

            if ($isCli) {
                echo "\n💡 [GEARS DUMP]:\n" . $output . "\n";
                continue;
            }

            // Inject parent root array metadata instantly if top-level structure matches
            if (is_array($var)) {
                $output = 'array:' . count($var) . substr($output, 5);
            }

            $tokens = token_get_all("<?php " . $output);
            $compiledTokens = [];
            $tokenCount = 0;

            foreach ($tokens as $token) {
                if (is_array($token)) {
                    if ($token[0] === T_OPEN_TAG) continue;

                    $text = htmlspecialchars($token[1]);

                    // Rewrite nested array keywords on the fly to support array:N layout mapping
                    if (($token[0] === T_STRING || $token[0] === 126) && str_starts_with(strtolower($text), 'array:')) {
                        $compiledTokens[$tokenCount++] = ['type' => 'BADGE', 'text' => '<span style="' . $badge_style . '">' . $text . '</span>'];
                    } else {
                        $compiledTokens[$tokenCount++] = ['type' => $token[0], 'text' => '<span style="' . ($styles[$token[0]] ?? 'color: #a626a4;') . '">' . $text . '</span>'];
                    }
                } else {
                    // Mutate round array symbols to square brackets instantly
                    $text = $token;
                    if ($text === '(') $text = '[';
                    elseif ($text === ')') $text = ']';

                    $textHtml = htmlspecialchars($text);
                    $style = ($token === '=>') ? $arrow_style : $char_style;

                    $compiledTokens[$tokenCount++] = ['type' => ($token === '=>' ? T_DOUBLE_ARROW : 'CHAR'), 'text' => '<span style="' . $style . '">' . $textHtml . '</span>'];
                }
            }

            // Bulletproof non-destructive native pass to assign custom theme styles on active array keys
            for ($i = 0; $i < $tokenCount; $i++) {
                if ($compiledTokens[$i]['type'] === T_DOUBLE_ARROW) {
                    $prev = $i - 1;
                    while ($prev >= 0 && $compiledTokens[$prev]['type'] === T_WHITESPACE) {
                        $prev--;
                    }
                    if ($prev >= 0 && in_array($compiledTokens[$prev]['type'], [T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER, T_STRING])) {
                        // Extract raw text from the compiled token wrapper to switch style safely
                        preg_match('/<span[^>]*>(.*)<\/span>/s', $compiledTokens[$prev]['text'], $match);
                        if (isset($match[1])) {
                            $compiledTokens[$prev]['text'] = '<span style="' . $key_style . '">' . $match[1] . '</span>';
                        }
                    }
                }
            }

            // Assemble optimized flat linear token layout stream
            $html = '';
            for ($i = 0; $i < $tokenCount; $i++) {
                $html .= $compiledTokens[$i]['text'];
            }

            echo '<pre style="background: #fdfdfd; border: 1px solid #e9ecef; padding: 8px 12px; font-family: monospace; font-size: 12px; margin: 8px 0; overflow-x: auto; border-radius: 4px; line-height: 1.4;">';
            echo $html;
            echo '</pre>';
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