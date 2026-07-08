<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\View\Exception;

/**
 * @package    Gears\Framework
 * @subpackage View
 */
class TemplateSyntaxException extends EngineException
{
    public function __construct(
        string $message,
        string $filePath,
        int $lineNo,
        int $column
    ) {
        parent::__construct(
            sprintf(
                '%s in %s at line %d, column %d',
                $message,
                $filePath,
                $lineNo,
                $column
            )
        );
    }
}