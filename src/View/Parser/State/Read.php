<?php
/**
 * @author Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Parser\State;

use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

class Read extends State
{
    public function run($char, Parser $parser)
    {
        if ('<' == $char) {
            $parser->switchState(Tag::class);
        }
    }
}
