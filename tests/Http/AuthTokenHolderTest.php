<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Http;

use Nyholm\Psr7\Response;
use PhPicnic\Auth\InMemoryAuthTokenStore;
use PhPicnic\Http\AuthTokenHolder;
use PHPUnit\Framework\TestCase;

final class AuthTokenHolderTest extends TestCase
{
    public function testStartsFromTheCachedTokenBeforeTheStore(): void
    {
        $store = new InMemoryAuthTokenStore();
        $store->save('stored');

        self::assertSame('cached', new AuthTokenHolder($store, 'cached')->current());
    }

    public function testFallsBackToTheStore(): void
    {
        $store = new InMemoryAuthTokenStore();
        $store->save('stored');

        self::assertSame('stored', new AuthTokenHolder($store)->current());
    }

    public function testEmptyStoredTokenMeansNoToken(): void
    {
        $holder = new AuthTokenHolder(new InMemoryAuthTokenStore());

        self::assertNull($holder->current());
        self::assertFalse($holder->isPresent());
    }

    public function testRotationUpdatesTheHolderAndTheStore(): void
    {
        $store = new InMemoryAuthTokenStore();
        $holder = new AuthTokenHolder($store, 'old');

        $holder->rotateFrom(new Response(200, ['x-picnic-auth' => ' new ']));

        self::assertSame('new', $holder->current());
        self::assertSame('new', $store->load());
    }

    public function testResponseWithoutTokenKeepsTheCurrentOne(): void
    {
        $store = new InMemoryAuthTokenStore();
        $holder = new AuthTokenHolder($store, 'old');

        $holder->rotateFrom(new Response(200));

        self::assertSame('old', $holder->current());
        self::assertNull($store->load());
    }

    public function testForgetClearsMemoryButNotTheStore(): void
    {
        $store = new InMemoryAuthTokenStore();
        $store->save('kept');

        $holder = new AuthTokenHolder($store);

        $holder->forget();

        self::assertFalse($holder->isPresent());
        self::assertSame('kept', $store->load());
    }
}
