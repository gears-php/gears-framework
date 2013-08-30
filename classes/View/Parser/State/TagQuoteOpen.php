<?php
/**
 * @author Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 */
namespace Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser\State;
use Gears\Framework\View\Parser;

class TagQuoteOpen extends State
{
	protected $quoteSymbol = '';

	public function getQuoteSymbol()
	{
		return $this->quoteSymbol;
	}

	public function getProcessedBuffer()
	{
		return '';
	}

	public function run($char, Parser $parser)
	{
		// empty quotes
		if ($parser->isChar('\'\'', -1) || $parser->isChar('""', -1)) {
			$parser->switchState('TagQuoteClose');
		} elseif ('\'' == $char || '"' == $char) {
			$this->quoteSymbol = $char;
			$this->addBuffer($char);
			$parser->readChar();
			$parser->switchState('TagAttrValue');
		}
	}
}