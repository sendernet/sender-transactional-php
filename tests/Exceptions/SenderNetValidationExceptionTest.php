<?php

namespace SenderNet\Tests\Exceptions;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use SenderNet\Exceptions\SenderNetValidationException;
use SenderNet\Tests\TestCase;

class SenderNetValidationExceptionTest extends TestCase
{
    public function test_get_first_error_returns_first_message(): void
    {
        $request = new Request('POST', 'https://api.sender.net/v2/message/send');
        $payload = [
            'message' => 'Validation failed',
            'errors' => [
                'field' => ['First error', 'Second error'],
            ],
        ];
        $response = new Response(422, ['Content-Type' => 'application/json'], json_encode($payload));

        $exception = new SenderNetValidationException($request, $response);

        self::assertSame('field: First error', $exception->getFirstError());
        self::assertSame([
            'field: First error',
            'field: Second error',
        ], $exception->getErrorMessages());
    }

    public function test_get_first_error_handles_scalar_errors(): void
    {
        $request = new Request('POST', 'https://api.sender.net/v2/message/send');
        $payload = [
            'message' => 'Validation failed',
            'errors' => [
                'field' => 'Single error',
            ],
        ];
        $response = new Response(422, ['Content-Type' => 'application/json'], json_encode($payload));

        $exception = new SenderNetValidationException($request, $response);

        self::assertSame('field: Single error', $exception->getFirstError());
        self::assertSame([
            'field: Single error',
        ], $exception->getErrorMessages());
    }

    public function test_get_first_error_returns_null_when_no_errors(): void
    {
        $request = new Request('POST', 'https://api.sender.net/v2/message/send');
        $payload = [
            'message' => 'Validation failed',
            'errors' => [],
        ];
        $response = new Response(422, ['Content-Type' => 'application/json'], json_encode($payload));

        $exception = new SenderNetValidationException($request, $response);

        self::assertNull($exception->getFirstError());
        self::assertSame([], $exception->getErrorMessages());
    }
}
