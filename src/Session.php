<?php

declare(strict_types=1);

namespace PhPicnic;

use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use JsonException;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorException;
use PhPicnic\Exception\TwoFactorRequiredException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Transport + authentication layer.
 *
 * Owns a single, reused PSR-18 client, the persistent request headers, and the
 * JSON encode/decode + error handling shared by every API call. Notably it:
 *  - sends the client-identity headers Picnic now requires (x-picnic-agent / -did);
 *  - refreshes the rotating x-picnic-auth token from every response;
 *  - surfaces auth errors that Picnic returns as HTTP 200 with an error body;
 *  - detects the two-factor-authentication-required login response.
 */
final class Session
{
    private const string AUTH_HEADER = 'x-picnic-auth';

    /** Error codes Picnic returns (inside an HTTP 200 body) for auth failures. */
    private const array AUTH_ERROR_CODES = ['AUTH_ERROR', 'AUTH_INVALID_CRED'];

    /** @var array<string, string> */
    private array $headers;

    public function __construct(
        private readonly PicnicConfig $config,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
        $this->headers = [
            'User-Agent' => $config->userAgent,
            'Content-Type' => 'application/json; charset=UTF-8',
            'x-picnic-agent' => $config->picnicAgent,
            'x-picnic-did' => $config->picnicDeviceId,
        ];

        if ($config->authToken !== null && $config->authToken !== '') {
            $this->headers[self::AUTH_HEADER] = $config->authToken;
        }
    }

    /**
     * Build a session that auto-discovers the PSR-18 client and PSR-17
     * factories for every dependency not passed explicitly.
     */
    public static function discover(
        PicnicConfig $config,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ): self {
        // Http\Discovery\Psr17Factory is a discovery-backed wrapper implementing
        // every PSR-17 factory interface; cheap to instantiate, no hard nyholm dep.
        $discoveredPsr17Factory = new Psr17Factory();

        return new self(
            $config,
            $httpClient ?? Psr18ClientDiscovery::find(),
            $requestFactory ?? $discoveredPsr17Factory,
            $streamFactory ?? $discoveredPsr17Factory,
        );
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
     * @throws InvalidCredentialsException    on bad credentials / missing token
     * @throws PicnicApiException
     */
    public function login(string $username, string $password): void
    {
        unset($this->headers[self::AUTH_HEADER]);

        $loginResponse = $this->send('POST', '/user/login', [
            'key' => $username,
            'secret' => md5($password),
            'client_id' => $this->config->clientId,
        ]);

        $loginResponseBody = $this->decode($loginResponse);

        if (($loginResponseBody['second_factor_authentication_required'] ?? false) === true) {
            throw new TwoFactorRequiredException(
                $this->errorMessage($loginResponseBody) ?? 'Two-factor authentication required.',
                $loginResponseBody,
            );
        }

        $this->assertNoAuthError($loginResponseBody);

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
    public function twoFactor(string $path, array $payload): void
    {
        $response = $this->send('POST', $path, $payload);

        $rawResponseBody = (string) $response->getBody();
        if ($response->getStatusCode() === 204 || $rawResponseBody === '') {
            return;
        }

        $body = $this->decode($response);
        $this->assertNoAuthError($body);

        $code = $this->errorCode($body);
        if ($code !== null) {
            throw new TwoFactorException(
                $this->errorMessage($body) ?? 'Two-factor authentication failed.',
                $code,
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
        $decodedBody = $this->decode($this->send($method, $path, $body));
        $this->assertNoAuthError($decodedBody);

        return $decodedBody;
    }

    /**
     * @param array<mixed>|string|null $body
     *
     * @throws PicnicApiException
     */
    private function send(string $method, string $path, array|string|null $body = null): ResponseInterface
    {
        $request = $this->requestFactory->createRequest($method, $this->config->baseUrl() . $path);

        foreach ($this->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $jsonRequestBody = json_encode($body, JSON_THROW_ON_ERROR);
            $request = $request->withBody($this->streamFactory->createStream($jsonRequestBody));
        }

        try {
            $response = $this->httpClient->sendRequest($request);
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
     * @return array<mixed>
     *
     * @throws PicnicApiException
     */
    private function decode(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        if ($body === '') {
            return [];
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new PicnicApiException(
                'Failed to decode JSON response from the Picnic API: ' . $jsonException->getMessage(),
                $response->getStatusCode(),
                $body,
                $jsonException,
            );
        }

        return is_array($decoded) ? $decoded : ['value' => $decoded];
    }

    /**
     * @param array<mixed> $body
     *
     * @throws InvalidCredentialsException
     */
    private function assertNoAuthError(array $body): void
    {
        $code = $this->errorCode($body);
        if ($code !== null && in_array($code, self::AUTH_ERROR_CODES, true)) {
            throw new InvalidCredentialsException(
                $this->errorMessage($body) ?? 'Picnic authentication error.',
            );
        }
    }

    /**
     * @param array<mixed> $body
     */
    private function errorCode(array $body): ?string
    {
        $error = $body['error'] ?? null;
        $code = is_array($error) ? ($error['code'] ?? null) : null;

        return is_string($code) ? $code : null;
    }

    /**
     * @param array<mixed> $body
     */
    private function errorMessage(array $body): ?string
    {
        $error = $body['error'] ?? null;
        $message = is_array($error) ? ($error['message'] ?? null) : null;

        return is_string($message) ? $message : null;
    }
}
