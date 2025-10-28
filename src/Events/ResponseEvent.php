<?php

declare(strict_types=1);

namespace Gears\Framework\Events;

use Symfony\Component\HttpFoundation\Response;

class ResponseEvent implements EventInterface
{
    public function __construct(private readonly Response $response)
    {
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}