<?php

namespace SenderNet\Helpers;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SenderNet\Exceptions\SenderNetHttpException;
use SenderNet\Exceptions\SenderNetRateLimitException;
use SenderNet\Exceptions\SenderNetValidationException;

class HttpErrorMapper
{
    public static function map(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $code = $response->getStatusCode();

        if ($code >= 200 && $code < 400) {
            return $response;
        }

        if ($code === 422) {
            throw new SenderNetValidationException($request, $response);
        }

        if ($code === 429) {
            throw new SenderNetRateLimitException($request, $response);
        }

        throw new SenderNetHttpException($request, $response);
    }
}
