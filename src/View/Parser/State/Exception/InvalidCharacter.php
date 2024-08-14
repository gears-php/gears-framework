<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Parser\State\Exception;

/**
 * Thrown by a specific state in case it meets an input stream character which
 * can not be processed properly (should not appear for that state)
 *
 * @package    Gears\Framework
 * @subpackage View
 */
class InvalidCharacter extends \Exception
{
    public function __construct($stateClass, $char, $pos, $file = '')
    {
        parent::__construct(sprintf('%s found invalid character "%s" in %s:%s',
            (new \ReflectionClass($stateClass))->getShortName(),
            $char,
            $file,
            $pos,
        ));
    }
}