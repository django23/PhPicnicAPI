<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * Progress of a checkout transaction or order. Poll until {@see isFinished()}.
 */
final readonly class CheckoutStatus
{
    /**
     * @param array<mixed> $raw
     */
    public function __construct(
        public string $checkoutStatus,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            checkoutStatus: PayloadReader::readRequiredString($payload, 'checkout_status'),
            raw: $payload,
        );
    }

    public function isFinished(): bool
    {
        return $this->checkoutStatus === 'FINISHED';
    }
}
