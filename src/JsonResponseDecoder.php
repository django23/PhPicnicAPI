<?php

declare(strict_types=1);

namespace PhPicnic;

use JsonException;
use PhPicnic\Exception\PicnicApiException;
use Psr\Http\Message\ResponseInterface;

/**
 * Turns a Picnic response body into an array.
 */
final class JsonResponseDecoder
{
    /**
     * An empty body decodes to an empty array; a JSON scalar is wrapped as
     * ['value' => scalar] so callers always get an array.
     *
     * @return array<mixed>
     *
     * @throws PicnicApiException when the body is not valid JSON
     */
    public static function decode(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        if ($body === '') {
            return [];
        }

        try {
            $decodedBody = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new PicnicApiException(
                'Failed to decode JSON response from the Picnic API: ' . $jsonException->getMessage(),
                $response->getStatusCode(),
                $body,
                $jsonException,
            );
        }

        return is_array($decodedBody) ? $decodedBody : ['value' => $decodedBody];
    }
}
