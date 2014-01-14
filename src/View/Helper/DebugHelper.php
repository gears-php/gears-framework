<?php
namespace Gears\Framework\View\Helper;

use Gears\Framework\Debug;
use Gears\Framework\Session;
use Gears\Framework\View\Helper;

/**
 * Class DebugHelper
 * @package Gears\Framework\View\Helper
 * TODO: refactoring
 */
class DebugHelper extends Helper
{
    /**
     * Returns various system debug info
     *
     * @return string HTML content
     */
    public function get()
    {
        if (Debug::enabled()) {
            Debug::add('-- $_GET:', $_GET, '-- $_POST:', $_POST);
            Debug::add('-- Gears\Framework\Session:', Session::get(null));

            return sprintf('%s<br />script <b>mem usage</b> %s<br />script <b>time</b> %s sec<br />',
                Debug::get(), Debug::getMemoryUsage(), Debug::scriptTime());
        }
    }

    /**
     *
     */
    public function console($opened = false)
    {
        if (Debug::enabled()):

            ob_start();

            ?>
            <!-- debug console -->
            <style type="text/css">
                #debug {
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

                #debug-body pre {
                    font: normal 10pt consolas, "courier new", courier, monospace;
                    color: #eee;
                    text-align: left;
                    margin: 5px 7px;
                }
            </style>
            <script type="text/javascript">
                (function () {
                    function toggle(el) {
                        el.style.display = (el.style.display == 'none') ? '' : 'none';
                    }

                    document.addEventListener("DOMContentLoaded", function () {
                        // console div container
                        var div = document.createElement('div');
                        div.setAttribute('id', 'debug');
                        div.innerHTML = '<div id="debug-body"><pre><?php echo str_replace("\n", '\\n', self::get()); ?></pre></div>';
                        document.body.appendChild(div);

                        document.onkeyup = function (e) {
                            var KEY_TILDE = 192;
                            if (e.which == KEY_TILDE && e.ctrlKey) {
                                toggle(div);
                                return false;
                            }
                        };

                        <?php
                        if (!$opened):
                            ?>toggle(div);
                        <?php
                                            endif;
                                            ?>
                    }, false);
                })();
            </script>
            <!-- /debug console -->
            <?php

            return ob_get_clean();

        endif;
    }
}