<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorException;
use PhPicnic\Exception\TwoFactorRequiredException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Transport + authentication layer.
 *
 * Owns the persistent request headers (including the rotating auth token) and
 * sends every API call through the {@see HttpTransport}. Notably it:
 *  - sends the client-identity headers Picnic now requires (x-picnic-agent / -did);
 *  - refreshes the rotating x-picnic-auth token from every response;
 *  - surfaces auth errors that Picnic returns as HTTP 200 with an error body;
 *  - detects the two-factor-authentication-required login response.
 */
final class Session
{
    private const string AUTH_HEADER = 'x-picnic-auth';

    /** @var array<string, string> */
    private array $headers;

    public function __construct(
        private readonly PicnicConfig $config,
        private readonly HttpTransport $transport,
        ?string $cachedAuthToken = null,
    ) {
        $this->headers = $config->defaultHeaders();

        if ($cachedAuthToken !== null && $cachedAuthToken !== '') {
            $this->headers[self::AUTH_HEADER] = $cachedAuthToken;
        }
    }

    public function isAuthenticated(): bool
    {
        return isset($this->headers[self::AUTH_HEADER]) && $this->headers[self::AUTH_HEADER] !== '';
    }

    public function authToken(): ?string
    {
        return $this->headers[self::AUTH_HEADER] ?? null;
    }

    /**
     * Exchange credentials for an auth token and store it for later requests.
     *
     * @throws TwoFactorRequiredException when the account needs a second factor
     * @throws InvalidCredentialsException on bad credentials / missing token
     * @throws PicnicApiException
     */
    public function login(Credentials $credentials): void
    {
        unset($this->headers[self::AUTH_HEADER]);

        $loginResponse = $this->send('POST', ApiEndpoint::LOGIN->path(), [
            'key' => $credentials->username,
            'secret' => md5($credentials->password),
            'client_id' => $this->config->identity->clientId,
        ]);

        $loginResponseBody = JsonResponseDecoder::decode($loginResponse);
        $error = ApiErrorBody::fromResponseBody($loginResponseBody);

        if (($loginResponseBody['second_factor_authentication_required'] ?? false) === true) {
            throw new TwoFactorRequiredException(
                $error->message ?? 'Two-factor authentication required.',
                $loginResponseBody,
            );
        }

        $this->assertNoAuthError($error);

        if (! $this->isAuthenticated()) {
            throw new InvalidCredentialsException(
                'Login failed: the Picnic API did not return an auth token. Check your credentials.',
            );
        }
    }

    /**
     * Drive a 2FA endpoint (generate/verify). These can answer with HTTP 204 /
     * an empty body on success, or an error body with a code on failure.
     *
     * @param array<mixed> $payload
     *
     * @throws TwoFactorException
     * @throws PicnicApiException
     */
    public function twoFactor(ApiEndpoint $endpoint, array $payload): void
    {
        $response = $this->send('POST', $endpoint->path(), $payload);

        if ($response->getStatusCode() === 204 || (string) $response->getBody() === '') {
            return;
        }

        $error = ApiErrorBody::fromResponseBody(JsonResponseDecoder::decode($response));
        $this->assertNoAuthError($error);

        if ($error->code !== null) {
            throw new TwoFactorException(
                $error->message ?? 'Two-factor authentication failed.',
                $error->code,
            );
        }
    }

    /**
     * @return array<mixed>
     *
     * @throws PicnicApiException
     * @throws InvalidCredentialsException
     */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /**
     * @param array<mixed>|string $payload
     *
     * @return array<mixed>
     *
     * @throws PicnicApiException
     * @throws InvalidCredentialsException
     */
    public function post(string $path, array|string $payload = []): array
    {
        return $this->request('POST', $path, $payload);
    }

    /**
     * @param array<mixed>|string|null $body
     *
     * @return array<mixed>
     */
    private function request(string $method, string $path, array|string|null $body = null): array
    {
        $decodedBody = JsonResponseDecoder::decode($this->send($method, $path, $body));
        $this->assertNoAuthError(ApiErrorBody::fromResponseBody($decodedBody));

        return $decodedBody;
    }

    /**
     * @param array<mixed>|string|null $body
     *
     * @throws PicnicApiException
     */
    private function send(string $method, string $path, array|string|null $body = null): ResponseInterface
    {
        $request = $this->transport->createRequest($method, $this->config->location->baseUrl() . $path);

        foreach ($this->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $jsonRequestBody = json_encode($body, JSON_THROW_ON_ERROR);
            $request = $request->withBody($this->transport->createStream($jsonRequestBody));
        }

        try {
            $response = $this->transport->sendRequest($request);
        } catch (ClientExceptionInterface $clientException) {
            throw new PicnicApiException(
                sprintf('HTTP request to "%s" failed: %s', $path, $clientException->getMessage()),
                0,
                '',
                $clientException,
            );
        }

        $this->refreshAuthToken($response);

        $httpStatusCode = $response->getStatusCode();
        if ($httpStatusCode < 200 || $httpStatusCode >= 300) {
            throw new PicnicApiException(
                sprintf('Picnic API returned HTTP %d for "%s".', $httpStatusCode, $path),
                $httpStatusCode,
                (string) $response->getBody(),
            );
        }

        return $response;
    }

    /**
     * The auth token rotates: capture the latest one Picnic sends back.
     */
    private function refreshAuthToken(ResponseInterface $response): void
    {
        $rotatedToken = $response->getHeaderLine(self::AUTH_HEADER);
        if ($rotatedToken !== '') {
            $this->headers[self::AUTH_HEADER] = $rotatedToken;
        }
    }

    /**
     * @throws InvalidCredentialsException
     */
    private function assertNoAuthError(ApiErrorBody $error): void
    {
        if ($error->isAuthError()) {
            throw new InvalidCredentialsException($error->message ?? 'Picnic authentication error.');
        }
    }
}
