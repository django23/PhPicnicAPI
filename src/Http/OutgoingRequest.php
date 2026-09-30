<?php

declare(strict_types=1);

namespace PhPicnic\Http;

/**
 * Everything needed to build one PSR-7 request. The label is what error messages
 * name (the API path, never a URL that could carry a secret).
 */
final readonly class OutgoingRequest
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $method,
        public string $url,
        public array $headers,
        public ?string $encodedBody,
        public string $label,
    ) {
    }
}
