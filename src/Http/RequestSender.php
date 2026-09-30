<?php

declare(strict_types=1);

namespace PhPicnic\Http;

use PhPicnic\Exception\PicnicApiException;
use PhPicnic\HttpTransport;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Builds a PSR-7 request and sends it. The three methods differ in what happens
 * to the response: only the authenticated one touches the token, and only the
 * redirect one lets non-2xx answers through (the caller inspects each hop).
 * Nothing retries: a failed POST or PUT may still have changed state.
 */
final readonly class RequestSender
{
    public function __construct(
        private HttpTransport $transport,
        private FailedResponseMapper $failedResponseMapper,
        private AuthTokenHolder $authToken,
    ) {
    }

    /**
     * @throws PicnicApiException
     */
    public function sendAuthenticated(OutgoingRequest $request): ResponseInterface
    {
        $response = $this->exchange($request);
        $this->authToken->rotateFrom($response);

        return $this->assertSuccessful($response, $request);
    }

    /**
     * @throws PicnicApiException
     */
    public function sendUnauthenticated(OutgoingRequest $request): ResponseInterface
    {
        return $this->assertSuccessful($this->exchange($request), $request);
    }

    /**
     * @throws PicnicApiException only on network failure
     */
    public function sendUnauthenticatedWithoutStatusCheck(OutgoingRequest $request): ResponseInterface
    {
        return $this->exchange($request);
    }

    /**
     * @throws PicnicApiException
     */
    private function exchange(OutgoingRequest $outgoing): ResponseInterface
    {
        $request = $this->transport->createRequest($outgoing->method, $outgoing->url);

        foreach ($outgoing->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($outgoing->encodedBody !== null) {
            $request = $request->withBody($this->transport->createStream($outgoing->encodedBody));
        }

        try {
            return $this->transport->sendRequest($request);
        } catch (ClientExceptionInterface $clientException) {
            throw new PicnicApiException(
                sprintf('HTTP request to "%s" failed: %s', $outgoing->label, $clientException->getMessage()),
                0,
                '',
                $clientException,
                $outgoing->method,
            );
        }
    }

    /**
     * @throws PicnicApiException
     */
    private function assertSuccessful(ResponseInterface $response, OutgoingRequest $request): ResponseInterface
    {
        $httpStatusCode = $response->getStatusCode();
        if ($httpStatusCode < 200 || $httpStatusCode >= 300) {
            throw $this->failedResponseMapper->exceptionFor($response, $request);
        }

        return $response;
    }
}
