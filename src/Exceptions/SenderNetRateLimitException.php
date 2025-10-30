<?php

namespace SenderNet\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class SenderNetRateLimitException extends SenderNetRequestException
{
    protected array $headers;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $this->headers = $response->getHeaders();

        parent::__construct($request, $response, self::buildMessage($request, $response));
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    protected static function buildMessage(RequestInterface $request, ResponseInterface $response): string
    {
        $retryAfter = $response->getHeaderLine('Retry-After');

        $base = parent::buildDefaultMessage($request, $response);

        return $retryAfter !== ''
            ? $base.' [retry after] '.$retryAfter
            : $base;
    }
}
