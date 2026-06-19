<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Support;

use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PhPicnic\Client;
use PhPicnic\Enum\CountryCode;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Base test case wiring a {@see Client} to an in-memory PSR-18 mock client, so
 * the suite never touches the network.
 */
abstract class PicnicTestCase extends TestCase
{
    protected MockClient $http;

    protected Psr17Factory $psr17;

    protected function setUp(): void
    {
        $this->http = new MockClient();
        $this->psr17 = new Psr17Factory();
    }

    /**
     * Queue the login response (carrying the auth token header) plus any number
     * of follow-up JSON responses, in the order they will be requested.
     *
     * @param array<mixed> ...$jsonBodies
     */
    protected function queueLoginThen(array ...$jsonBodies): void
    {
        $this->http->addResponse(
            (new Response(200))->withHeader('x-picnic-auth', 'test-token'),
        );

        foreach ($jsonBodies as $body) {
            $this->queueJson($body);
        }
    }

    /**
     * @param array<mixed> $body
     */
    protected function queueJson(array $body, int $status = 200): void
    {
        $this->http->addResponse(new Response(
            $status,
            ['Content-Type' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR),
        ));
    }

    protected function makeClient(?string $authToken = null): Client
    {
        return new Client(
            username: 'user@example.com',
            password: 'secret',
            countryCode: CountryCode::NL,
            httpClient: $this->http,
            requestFactory: $this->psr17,
            streamFactory: $this->psr17,
            apiVersion: '15',
            authToken: $authToken,
        );
    }

    /**
     * The request the client actually sent at the given index (0-based).
     */
    protected function sentRequest(int $index): RequestInterface
    {
        $requests = $this->http->getRequests();
        self::assertArrayHasKey($index, $requests, "No request was sent at index {$index}.");

        return $requests[$index];
    }

    /**
     * @return array<mixed>
     */
    protected function sentJsonBody(int $index): array
    {
        return json_decode((string) $this->sentRequest($index)->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
