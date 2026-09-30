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
            '/lists/a%2Fb?sublist=c%20d',
            ApiEndpoint::SHOPPING_LIST_SUBLIST->path('a/b', 'c d'),
        );
    }
}
