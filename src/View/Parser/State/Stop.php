<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */

namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

/**
 * Stop state signals tag processing finish
 */
class Stop extends State
{
    public function process($char, Parser $parser): void
    {
        $parser->nextChar();
    }
}
