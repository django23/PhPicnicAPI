<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Auth;

use PhPicnic\Auth\FileAuthTokenStore;
use PhPicnic\Auth\InMemoryAuthTokenStore;
use PHPUnit\Framework\TestCase;

final class FileAuthTokenStoreTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/picnic-token-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function testLoadReturnsNullWhenNothingWasSaved(): void
    {
        self::assertNull(new FileAuthTokenStore($this->path)->load());
    }

    public function testSavedTokenIsReadableOnlyByTheCurrentUser(): void
    {
        $store = new FileAuthTokenStore($this->path);
        $store->save('token-1');

        self::assertSame('token-1', $store->load());
        self::assertSame('0600', substr(sprintf('%o', fileperms($this->path)), -4));
    }

    public function testSaveReplacesThePreviousToken(): void
    {
        $store = new FileAuthTokenStore($this->path);
        $store->save('token-1');
        $store->save('token-2');

        self::assertSame('token-2', $store->load());
    }

    public function testInMemoryStoreKeepsTheLastToken(): void
    {
        $store = new InMemoryAuthTokenStore();
        $store->save('token-1');

        self::assertSame('token-1', $store->load());
    }
}
