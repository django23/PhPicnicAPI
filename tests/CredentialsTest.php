<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use PhPicnic\Credentials;
use PhPicnic\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

final class CredentialsTest extends TestCase
{
    public function testFromPasswordStoresOnlyTheMd5Secret(): void
    {
        $credentials = Credentials::fromPassword('user@example.com', 'secret');

        self::assertSame(md5('secret'), $credentials->secret);
    }

    public function testFromHashedSecretKeepsTheGivenHash(): void
    {
        $credentials = Credentials::fromHashedSecret('user@example.com', md5('secret'), 'token');

        self::assertSame(md5('secret'), $credentials->secret);
        self::assertSame('token', $credentials->cachedAuthToken);
    }

    public function testRejectsAPasswordThatIsNotUtf8(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        Credentials::fromPassword('user@example.com', "\xFF\xFE");
    }
}
