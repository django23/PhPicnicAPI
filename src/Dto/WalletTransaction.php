<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * A payment or refund in the wallet. Amounts are in cents, the timestamp in milliseconds.
 */
final readonly class WalletTransaction
{
    /**
     * @param array<mixed> $raw
     */
    public function __construct(
        public string $id,
        public ?int $amountInCents,
        public ?string $displayName,
        public ?string $status,
        public ?int $timestamp,
        public ?string $transactionType,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: PayloadReader::readRequiredString($payload, 'id'),
            amountInCents: PayloadReader::readInt($payload, 'amount_in_cents'),
            displayName: PayloadReader::readString($payload, 'display_name'),
            status: PayloadReader::readString($payload, 'status'),
            timestamp: PayloadReader::readInt($payload, 'timestamp'),
            transactionType: PayloadReader::readString($payload, 'transaction_type'),
            raw: $payload,
        );
    }
}
