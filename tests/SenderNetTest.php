<?php

namespace SenderNet\Tests;

use SenderNet\Helpers\Arr;
use SenderNet\Endpoints\Email;
use SenderNet\Exceptions\SenderNetException;
use SenderNet\SenderNet;
use ReflectionClass;

class SenderNetTest extends TestCase
{
    public function test_should_fail_without_api_key(): void
    {
        $this->expectException(SenderNetException::class);

        new SenderNet();
    }

    public function test_should_have_email_endpoint_set(): void
    {
        $sdk = new SenderNet([
            'api_key' => 'test'
        ]);

        self::assertInstanceOf(Email::class, $sdk->email);
    }

    public function test_should_get_api_key_from_env(): void
    {
        putenv('SENDER_API_KEY=test');

        $sdk = new SenderNet();

        $reflection = new ReflectionClass($sdk);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);

        self::assertEquals('test', Arr::get($property->getValue($sdk), 'api_key'));
    }

    public function test_should_override_api_key_if_provided(): void
    {
        putenv('SENDER_API_KEY=test');

        $sdk = new SenderNet(['api_key' => 'key']);

        $reflection = new ReflectionClass($sdk);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);

        self::assertEquals('key', Arr::get($property->getValue($sdk), 'api_key'));
    }
}
