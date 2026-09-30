<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Http;

use Nyholm\Psr7\Response;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\TwoFactorException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\Http\AuthResponseGuard;
use PHPUnit\Framework\TestCase;

final class AuthResponseGuardTest extends TestCase
{
    public function testAuthErrorInsideAnHttp200BodyThrows(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        new AuthResponseGuard()->assertNoAuthError(['error' => ['code' => 'AUTH_ERROR']]);
    }

    public function testOtherBodiesPass(): void
    {
        new AuthResponseGuard()->assertNoAuthError(['error' => ['code' => 'OTHER']]);

        $this->addToAssertionCount(1);
    }

    public function testLoginWithSecondFactorFlagThrows(): void
    {
        $this->expectException(TwoFactorRequiredException::class);

        new AuthResponseGuard()->assertLoginAccepted(['second_factor_authentication_required' => true]);
    }

    public function testLoginAuthErrorThrows(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        new AuthResponseGuard()->assertLoginAccepted(['error' => ['code' => 'AUTH_INVALID_CRED']]);
    }

    public function testTwoFactorAcceptsNoContentAndEmptyBody(): void
    {
        $guard = new AuthResponseGuard();
        $guard->assertTwoFactorAccepted(new Response(204), '/2fa');
        $guard->assertTwoFactorAccepted(new Response(200), '/2fa');

        $this->addToAssertionCount(1);
    }

    public function testTwoFactorErrorCodeThrows(): void
    {
        $this->expectException(TwoFactorException::class);

        new AuthResponseGuard()->assertTwoFactorAccepted(new Response(200, [], '{"error":{"code":"WRONG_OTP"}}'), '/2fa');
    }
}
