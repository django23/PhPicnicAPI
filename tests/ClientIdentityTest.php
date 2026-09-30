<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use PhPicnic\ClientIdentity;
use PhPicnic\Enum\AppProfile;
use PhPicnic\Exception\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;

final class ClientIdentityTest extends TestCase
{
    public function testDefaultsToTheNewestLiveVerifiedProfile(): void
    {
        $identity = new ClientIdentity();

        self::assertSame(AppProfile::V1_246_1->agentString(), $identity->picnicAgent);
        self::assertSame(ClientIdentity::SHARED_DEVICE_ID, $identity->picnicDeviceId);
    }

    public function testForProfilePresentsTheChosenAppVersion(): void
    {
        $identity = ClientIdentity::forProfile(AppProfile::V1_206_1);

        self::assertSame('30100;1.206.1-#15408', $identity->picnicAgent);
    }

    public function testWithProfileKeepsTheDeviceId(): void
    {
        $identity = ClientIdentity::forProfile(AppProfile::V1_206_1, 'ABCDEF0123456789')->withProfile(AppProfile::V1_236_1);

        self::assertSame('30100;1.236.1-15553;', $identity->picnicAgent);
        self::assertSame('ABCDEF0123456789', $identity->picnicDeviceId);
    }

    public function testGeneratedDeviceIdIsSixteenUppercaseHexCharactersAndDiffersEachTime(): void
    {
        $first = new ClientIdentity()->withGeneratedDeviceId();
        $second = new ClientIdentity()->withGeneratedDeviceId();

        self::assertMatchesRegularExpression('/^[0-9A-F]{16}$/', $first->picnicDeviceId);
        self::assertNotSame($first->picnicDeviceId, $second->picnicDeviceId);
    }

    public function testRejectsAMalformedDeviceId(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        new ClientIdentity()->withDeviceId('not-hex');
    }
}
