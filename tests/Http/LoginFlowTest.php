<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Http;

use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PhPicnic\Auth\InMemoryAuthTokenStore;
use PhPicnic\Credentials;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\Http\AuthTokenHolder;
use PhPicnic\Http\FailedResponseMapper;
use PhPicnic\Http\LoginFlow;
use PhPicnic\Http\RequestBuilder;
use PhPicnic\Http\RequestSender;
use PhPicnic\HttpTransport;
use PhPicnic\PicnicConfig;
use PHPUnit\Framework\TestCase;

final class LoginFlowTest extends TestCase
{
    private MockClient $http;

    private AuthTokenHolder $token;

    private LoginFlow $login;

    protected function setUp(): void
    {
        $this->http = new MockClient();
        $psr17 = new Psr17Factory();
        $config = new PicnicConfig();
        $this->token = new AuthTokenHolder(new InMemoryAuthTokenStore(), 'stale');
        $sender = new RequestSender(new HttpTransport($this->http, $psr17, $psr17), new FailedResponseMapper(), $this->token);
        $this->login = new LoginFlow($config, new RequestBuilder($config, $this->token), $sender, $this->token);
    }

    public function testLoginSendsCredentialsWithoutTheStaleTokenAndKeepsTheRotatedOne(): void
    {
        $this->http->addResponse(new Response(200, ['x-picnic-auth' => 'fresh'], '{}'));

        $this->login->perform(new Credentials('me@example.test', 'pw'));

        $sent = $this->http->getRequests()[0];
        self::assertFalse($sent->hasHeader('x-picnic-auth'));
        self::assertStringContainsString('"key":"me@example.test"', (string) $sent->getBody());
        self::assertSame('fresh', $this->token->current());
    }

    public function testMissingTokenMeansInvalidCredentials(): void
    {
        $this->http->addResponse(new Response(200, [], '{}'));

        $this->expectException(InvalidCredentialsException::class);

        $this->login->perform(new Credentials('me@example.test', 'pw'));
    }

    public function testSecondFactorFlagThrows(): void
    {
        $this->http->addResponse(new Response(200, [], '{"second_factor_authentication_required":true}'));

        $this->expectException(TwoFactorRequiredException::class);

        $this->login->perform(new Credentials('me@example.test', 'pw'));
    }
}
