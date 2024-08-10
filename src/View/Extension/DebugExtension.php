<?php

declare(strict_types=1);

namespace Gears\Framework\View\Extension;

use Gears\Framework\Debug as DebugInfo;
use Gears\Framework\Session;
use Gears\Framework\View\View;

class DebugExtension implements ExtensionInterface
{

    public function getName(): string
    {
        return 'debug';
    }

    /**
     * Returns various system debug info
     *
     * @return string HTML content
     */
    public function get(): string
    {
        if (DebugInfo::enabled()) {
            DebugInfo::add('-- $_GET:', $_GET, '-- $_POST:', $_POST);
            DebugInfo::add('-- Gears\Framework\Session:', Session::get(null));

            return sprintf(
                '%s<br />script <b>mem usage</b> %s<br />script <b>time</b> %s sec<br />',
                DebugInfo::get(),
                DebugInfo::getMemoryUsage(),
                DebugInfo::scriptTime()
            );
        }

        return '';
    }

    public function __invoke(array $params = null): string
    {
        if (DebugInfo::enabled()):
            ob_start();

            ?>
            <!-- debug console -->
            <style>
                .gears-debug {
                    top: 0;
                    left: 0;
                    position: fixed;
                    width: 100%;
                    background-color: #333;
                    z-index: 1000;
                    opacity: 0.9;
                    -moz-box-shadow: 0 2px 10px #555;
                    -webkit-box-shadow: 0 2px 10px #555;
                    box-shadow: 0 2px 10px #555;
                }

                .gears-debug-body pre {
                    font: normal 10pt consolas, "courier new", courier, monospace;
                    color: #eee;
                    text-align: left;
                    margin: 5px 7px;
                }
            </style>
            <script type="text/javascript">
                (function () {
                    function toggle(el) {
                        el.style.display = (el.style.display === 'none') ? '' : 'none';
                    }

                    document.addEventListener("DOMContentLoaded", function () {
                        // console div container
                        const div = document.createElement('div');
                        div.classList.add('gears-debug');
                        div.innerHTML = '<div class="gears-debug-body"><pre><?php echo str_replace(
                            "\n",
                            '\\n',
                            self::get()
                        ); ?></pre></div>';
                        document.body.appendChild(div);

                        document.onkeyup = function (e) {
                            if (e.key === '`' && e.ctrlKey) {
                                toggle(div);
                                return false;
                            }
                        };

                        <?php if (!($params['opened'] ?? false)): ?>
                        toggle(div);
                        <?php endif; ?>
                    }, false);
                })();
            </script>
            <!-- /debug console -->
            <?php

            return ob_get_clean();
        endif;

        return '';
    }

    public function setup(View $view)
    {
        // TODO: Implement setup() method.
    }
}