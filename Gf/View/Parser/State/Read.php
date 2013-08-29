<?php
/**
 * @author Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gf\View\Parser\State;
use Gf\View\Parser\State;
use Gf\View\Parser;

class Read extends State
{
	public function run($char, Parser $parser)
	{
		if ('<' == $char) {
			$parser->switchState('Tag');
		}
	}
}
