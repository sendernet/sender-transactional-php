<?php

namespace SenderNet\Exceptions;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class SenderNetRequestException extends SenderNetException implements RequestExceptionInterface
{
    protected RequestInterface $request;
    protected ResponseInterface $response;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        ?string $message = null
    ) {
        $this->request = $request;
        $this->response = $response;

        parent::__construct($message ?? static::buildDefaultMessage($request, $response), $response->getStatusCode());
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    protected static function buildDefaultMessage(RequestInterface $request, ResponseInterface $response): string
    {
        return sprintf(
            '[url] %s [http method] %s [status code] %s [reason phrase] %s',
            $request->getRequestTarget(),
            $request->getMethod(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );
    }
}
