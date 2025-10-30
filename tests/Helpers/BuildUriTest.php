<?php

namespace SenderNet\Tests\Helpers;

use SenderNet\Helpers\BuildUri;
use SenderNet\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class BuildUriTest extends TestCase
{
    /** @dataProvider build_uri_provider */
    #[DataProvider('build_uri_provider')]
    public function test_execute_build_uri(array $options, string $path, array $params, string $expected): void
    {
        $build_uri = (new BuildUri($options))->execute($path, $params);

        $this->assertEquals($expected, $build_uri);
    }

    public static function build_uri_provider(): array
    {
        return [
            [
                [
                    'host' => 'api.example.com',
                    'protocol' => 'https',
                    'api_path' => 'v1',
                ],
                'endpoint',
                [],
                'https://api.example.com/v1/endpoint'
            ],
            [
                [
                    'host' => 'sendernet.local',
                    'protocol' => 'http',
                    'api_path' => 'api/v1',
                ],
                'endpoint',
                [],
                'http://sendernet.local/api/v1/endpoint'
            ],
            [
                [
                    'host' => 'sendernet.local',
                    'protocol' => 'http',
                    'api_path' => 'api/v1',
                ],
                'endpoint',
                [
                    'first' => 'param',
                    'second' => 'param'
                ],
                'http://sendernet.local/api/v1/endpoint?first=param&second=param',
            ]
        ];
    }
}
