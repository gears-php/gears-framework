<?php
/**
 * @author Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gf\View\Parser\State;
use Gf\View\Parser\State;
use Gf\View\Parser;

class TagQuoteClose extends State
{
	public function getProcessedBuffer()
	{
		return sprintf(',');
	}

	public function run($char, Parser $parser)
	{
		if ('\'' == $char || '"' == $char) {
			$this->addBuffer($char);
		} elseif (' ' == $char) {
			$parser->switchState('TagSpace');
		} elseif ('/' == $char || '>' == $char) {
			$parser->switchState('TagClose');
		} elseif (preg_match('/\w/', $char)) {
			$parser->switchState('TagAttr');
		} else {
			$this->invalidCharacterException();
		}
	}
}