<?php

namespace SenderNet\Tests\Helpers\Builder;

use SenderNet\Helpers\Arr;
use SenderNet\Exceptions\SenderNetAssertException;
use SenderNet\Helpers\Builder\Header;
use SenderNet\Tests\TestCase;

class HeaderTest extends TestCase
{
    public function test_properly_sets_header_params(): void
    {
        $header = (new Header('Custom-Header-1', 'Value 1'))->toArray();

        self::assertEquals('Custom-Header-1', Arr::get($header, 'name'));
        self::assertEquals('Value 1', Arr::get($header, 'value'));
    }

    public function test_header_validates_empty_name(): void
    {
        $this->expectException(SenderNetAssertException::class);

        (new Header('', 'Value 1'));
    }

    public function test_header_validates_empty_value(): void
    {
        $this->expectException(SenderNetAssertException::class);

        (new Header('Custom-Header-1', ''));
    }
}
