<?php

declare(strict_types=1);

namespace Gears\Framework\View\Exception;

use Gears\Framework\View\TagContext;

class RenderingException extends EngineException
{
    public function __construct(string $message, TagContext $ctx)
    {
        parent::__construct(
            sprintf(
                '%s. Check &lt;%s&gt; tag in %s at line %d, column %d',
                $message,
                $ctx->name,
                $ctx->filePath,
                $ctx->linepos,
                $ctx->column
            )
        );
    }
}