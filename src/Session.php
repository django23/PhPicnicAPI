<?php

declare(strict_types=1);

namespace PhPicnic;

use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use PhPicnic\Exception\AuthenticationException;
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

    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    /** @var array<string, string> */
    private array $headers;

    public function __construct(
        private readonly PicnicConfig $config,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();

        // Http\Discovery\Psr17Factory is a discovery-backed wrapper implementing
        // every PSR-17 factory interface; cheap to instantiate, no hard nyholm dep.
        $psr17 = new Psr17Factory();
        $this->requestFactory = $requestFactory ?? $psr17;
        $this->streamFactory = $streamFactory ?? $psr17;

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
     * @throws AuthenticationException    on bad credentials / missing token
     * @throws PicnicApiException
     */
    public function login(string $username, string $password): void
    {
        unset($this->headers[self::AUTH_HEADER]);

        $response = $this->send('POST', '/user/login', [
            'key' => $username,
            'secret' => md5($password),
            'client_id' => $this->config->clientId,
        ]);

        $body = $this->decode($response);

        if (($body['second_factor_authentication_required'] ?? false) === true) {
            throw new TwoFactorRequiredException(
                $this->errorMessage($body) ?? 'Two-factor authentication required.',
                $body,
            );
        }

        $this->assertNoAuthError($body);

        if (! $this->isAuthenticated()) {
            throw new AuthenticationException(
                'Login failed: the Picnic API did not return an auth token. Check your credentials.',
            );
        }
    }

    /**
     * Drive a 2FA endpoint (generate/verify). These can answer with HTTP 204 /
     * an empty body on success, or an error body with a code on failure.
     *
     * @param array<mixed> $data
     *
     * @throws TwoFactorException
     * @throws PicnicApiException
     */
    public function twoFactor(string $path, array $data): void
    {
        $response = $this->send('POST', $path, $data);

        $raw = (string) $response->getBody();
        if ($response->getStatusCode() === 204 || $raw === '') {
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
     * @throws AuthenticationException
     */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /**
     * @param array<mixed>|string $data
     *
     * @return array<mixed>
     *
     * @throws PicnicApiException
     * @throws AuthenticationException
     */
    public function post(string $path, array|string $data = []): array
    {
        return $this->request('POST', $path, $data);
    }

    /**
     * @param array<mixed>|string|null $body
     *
     * @return array<mixed>
     */
    private function request(string $method, string $path, array|string|null $body = null): array
    {
        $decoded = $this->decode($this->send($method, $path, $body));
        $this->assertNoAuthError($decoded);

        return $decoded;
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
            $json = json_encode($body, JSON_THROW_ON_ERROR);
            $request = $request->withBody($this->streamFactory->createStream($json));
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new PicnicApiException(
                sprintf('HTTP request to "%s" failed: %s', $path, $e->getMessage()),
                0,
                '',
                $e,
            );
        }

        $this->refreshAuthToken($response);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new PicnicApiException(
                sprintf('Picnic API returned HTTP %d for "%s".', $status, $path),
                $status,
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
        $token = $response->getHeaderLine(self::AUTH_HEADER);
        if ($token !== '') {
            $this->headers[self::AUTH_HEADER] = $token;
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
        } catch (\JsonException $e) {
            throw new PicnicApiException(
                'Failed to decode JSON response from the Picnic API: ' . $e->getMessage(),
                $response->getStatusCode(),
                $body,
                $e,
            );
        }

        return is_array($decoded) ? $decoded : ['value' => $decoded];
    }

    /**
     * @param array<mixed> $body
     *
     * @throws AuthenticationException
     */
    private function assertNoAuthError(array $body): void
    {
        $code = $this->errorCode($body);
        if ($code !== null && in_array($code, self::AUTH_ERROR_CODES, true)) {
            throw new AuthenticationException(
                $this->errorMessage($body) ?? 'Picnic authentication error.',
            );
        }
    }

    /**
     * @param array<mixed> $body
     */
    private function errorCode(array $body): ?string
    {
        $code = $body['error']['code'] ?? null;

        return is_string($code) ? $code : null;
    }

    /**
     * @param array<mixed> $body
     */
    private function errorMessage(array $body): ?string
    {
        $message = $body['error']['message'] ?? null;

        return is_string($message) ? $message : null;
    }
}
