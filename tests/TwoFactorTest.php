<?php

declare(strict_types=1);

namespace PhPicnic\Tests;

use Nyholm\Psr7\Response;
use PhPicnic\Enum\TwoFactorChannel;
use PhPicnic\Exception\TwoFactorException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\Tests\Support\AbstractPicnicTestCase;

final class TwoFactorTest extends AbstractPicnicTestCase
{
    private const string BASE = 'https://storefront-prod.nl.picnicinternational.com/api/15';

    public function testLoginSurfacesTwoFactorRequired(): void
    {
        $this->http->addResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['second_factor_authentication_required' => true], JSON_THROW_ON_ERROR),
        ));

        $this->expectException(TwoFactorRequiredException::class);
        $this->makeClient()->authenticate();
    }

    public function testGenerateAndVerifyFlow(): void
    {
        $this->http->addResponse(new Response(204)); // generate
        $this->http->addResponse(new Response(204)->withHeader('x-picnic-auth', 'verified-token')); // verify

        $client = $this->makeClient(authToken: 'partial');
        $client->requestTwoFactorCode(TwoFactorChannel::SMS);
        $client->verifyTwoFactorCode('123456');

        self::assertSame(self::BASE . '/user/2fa/generate', (string) $this->sentRequest(0)->getUri());
        self::assertSame(['channel' => 'SMS'], $this->sentJsonBody(0));
        self::assertSame(self::BASE . '/user/2fa/verify', (string) $this->sentRequest(1)->getUri());
        self::assertSame(['otp' => '123456'], $this->sentJsonBody(1));
        self::assertSame('verified-token', $client->currentAuthToken());
    }

    public function testGenerateAcceptsStringChannel(): void
    {
        $this->http->addResponse(new Response(204));
        $this->makeClient(authToken: 'partial')->requestTwoFactorCode('email');

        self::assertSame(['channel' => 'EMAIL'], $this->sentJsonBody(0));
    }

    public function testVerifyInvalidCodeThrows(): void
    {
        $this->http->addResponse(new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => ['code' => 'INVALID_OTP', 'message' => 'Invalid code']], JSON_THROW_ON_ERROR),
        ));

        $this->expectException(TwoFactorException::class);
        $this->makeClient(authToken: 'partial')->verifyTwoFactorCode('000000');
    }
}
