<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use PhPicnic\Enum\ApiEndpoint;
use PHPUnit\Framework\TestCase;

final class ApiEndpointTest extends TestCase
{
    public function testPathWithoutPlaceholdersIsReturnedAsIs(): void
    {
        self::assertSame('/cart/clear', ApiEndpoint::CART_CLEAR->path());
    }

    public function testPathUrlEncodesEveryParameter(): void
    {
        self::assertSame(
            '/deliveries/a%2Fb%20c/scenario',
            ApiEndpoint::DELIVERY_SCENARIO->path('a/b c'),
        );
    }
}
