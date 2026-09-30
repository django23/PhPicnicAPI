<?php

declare(strict_types=1);

namespace PhPicnic;

use Http\Discovery\Psr17Factory;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * The PSR-18 client and PSR-17 factories that move requests over the wire.
 */
final readonly class HttpTransport
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * Use the given parts and auto-discover any that are missing.
     */
    public static function discover(
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ): self {
        // Http\Discovery\Psr17Factory is a discovery-backed wrapper implementing
        // every PSR-17 factory interface; cheap to instantiate, no hard nyholm dep.
        $discoveredPsr17Factory = new Psr17Factory();

        return new self(
            $httpClient ?? Psr18ClientDiscovery::find(),
            $requestFactory ?? $discoveredPsr17Factory,
            $streamFactory ?? $discoveredPsr17Factory,
        );
    }

    public function createRequest(string $method, string $url): RequestInterface
    {
        return $this->requestFactory->createRequest($method, $url);
    }

    public function createStream(string $content): StreamInterface
    {
        return $this->streamFactory->createStream($content);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->sendRequest($request);
    }
}
