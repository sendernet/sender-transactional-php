<?php

namespace SenderNet\Tests\Helpers;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use SenderNet\Exceptions\SenderNetHttpException;
use SenderNet\Exceptions\SenderNetRateLimitException;
use SenderNet\Exceptions\SenderNetValidationException;
use SenderNet\Helpers\HttpErrorMapper;
use SenderNet\Tests\TestCase;

class HttpErrorMapperTest extends TestCase
{
    public function test_map_returns_response_for_successful_status(): void
    {
        $request = new Request('GET', 'https://api.sender.net/v2/resource');
        $response = new Response(200, ['Content-Type' => 'application/json'], '{}');

        $result = HttpErrorMapper::map($request, $response);

        self::assertSame($response, $result);
    }

    public function test_map_throws_validation_exception_with_payload(): void
    {
        $request = new Request('POST', 'https://api.sender.net/v2/message/send');
        $payload = ['message' => 'Invalid data', 'errors' => ['field' => ['Required']]];
        $response = new Response(422, ['Content-Type' => 'application/json'], json_encode($payload));

        $this->expectException(SenderNetValidationException::class);
        $this->expectExceptionMessage('Invalid data');

        try {
            HttpErrorMapper::map($request, $response);
        } catch (SenderNetValidationException $exception) {
            self::assertSame(422, $exception->getStatusCode());
            self::assertSame($payload['errors'], $exception->getErrors());
            self::assertSame($payload, json_decode($exception->getBody(), true));

            throw $exception;
        }
    }

    public function test_map_throws_validation_exception_with_invalid_payload(): void
    {
        $request = new Request('POST', 'https://api.sender.net/v2/message/send');
        $response = new Response(422, ['Content-Type' => 'text/plain'], 'not-json');

        $this->expectException(SenderNetValidationException::class);
        $this->expectExceptionMessage('Validation Error');

        try {
            HttpErrorMapper::map($request, $response);
        } catch (SenderNetValidationException $exception) {
            self::assertSame([], $exception->getErrors());
            self::assertSame('not-json', $exception->getBody());

            throw $exception;
        }
    }

    public function test_map_throws_rate_limit_exception_with_retry_after(): void
    {
        $request = new Request('GET', 'https://api.sender.net/v2/resource');
        $response = new Response(429, ['Retry-After' => '120'], '');

        $this->expectException(SenderNetRateLimitException::class);
        $this->expectExceptionMessageMatches('/\[retry after] 120/');

        try {
            HttpErrorMapper::map($request, $response);
        } catch (SenderNetRateLimitException $exception) {
            self::assertSame(['Retry-After' => ['120']], $exception->getHeaders());
            self::assertSame($request, $exception->getRequest());
            self::assertSame($response, $exception->getResponse());

            throw $exception;
        }
    }

    public function test_map_throws_http_exception_for_unhandled_error_codes(): void
    {
        $request = new Request('GET', 'https://api.sender.net/v2/resource');
        $response = new Response(500, ['Content-Type' => 'application/json'], '{}');

        $expectedMessage = sprintf(
            '[url] %s [http method] %s [status code] %s [reason phrase] %s',
            $request->getRequestTarget(),
            $request->getMethod(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        $this->expectException(SenderNetHttpException::class);
        $this->expectExceptionMessage($expectedMessage);

        HttpErrorMapper::map($request, $response);
    }
}
