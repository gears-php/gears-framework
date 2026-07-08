<?php

declare(strict_types=1);

namespace Gears\Framework\View\Exception;

class TemplateFileNotFoundException extends EngineException
{
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct('Template file not found: ' . $message, $code, $previous);
    }
}