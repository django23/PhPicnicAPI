<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * A started payment. Send the customer to $redirectUrl (a bank page) to finish it.
 */
final readonly class PaymentInitiation
{
    /**
     * @param array<mixed> $raw
     */
    public function __construct(
        public string $paymentId,
        public string $transactionId,
        public ?string $redirectUrl,
        public ?string $issuerAuthenticationUrl,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $action = PayloadReader::readArray($payload, 'action');

        return new self(
            paymentId: PayloadReader::readRequiredString($payload, 'payment_id'),
            transactionId: PayloadReader::readRequiredString($payload, 'transaction_id'),
            redirectUrl: PayloadReader::readString($action, 'redirect_url'),
            issuerAuthenticationUrl: PayloadReader::readString($payload, 'issuer_authentication_url'),
            raw: $payload,
        );
    }
}
