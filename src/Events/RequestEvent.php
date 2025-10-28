<?php

declare(strict_types=1);

namespace Gears\Framework\Events;

use Gears\Framework\Application\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestEvent implements EventInterface
{
    private ?Response $response = null;

    public function __construct(private readonly Request $request, private readonly Route $route)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }
}