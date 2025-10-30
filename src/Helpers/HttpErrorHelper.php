<?php

namespace SenderNet\Helpers;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;
use SenderNet\Helpers\HttpErrorMapper;

class HttpErrorHelper implements Plugin
{
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        $promise = $next($request);

        return $promise->then(fn ($response) => HttpErrorMapper::map($request, $response));
    }
}
