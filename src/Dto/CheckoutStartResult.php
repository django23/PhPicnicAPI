<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * The order Picnic prepared when a checkout was started. Amounts are in cents.
 */
final readonly class CheckoutStartResult
{
    /**
     * @param array<mixed> $raw
     */
    public function __construct(
        public string $orderId,
        public ?int $totalPrice,
        public ?int $totalCount,
        public ?int $totalDeposit,
        public ?int $totalSavings,
        public ?string $transactionExpiry,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            orderId: PayloadReader::readRequiredString($payload, 'order_id'),
            totalPrice: PayloadReader::readInt($payload, 'total_price'),
            totalCount: PayloadReader::readInt($payload, 'total_count'),
            totalDeposit: PayloadReader::readInt($payload, 'total_deposit'),
            totalSavings: PayloadReader::readInt($payload, 'total_savings'),
            transactionExpiry: PayloadReader::readString($payload, 'transaction_expiry'),
            raw: $payload,
        );
    }
}
