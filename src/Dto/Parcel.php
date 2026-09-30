<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * A package shipped by an external carrier.
 */
final readonly class Parcel
{
    /**
     * @param array<mixed> $raw
     */
    public function __construct(
        public string $id,
        public ?string $handlerName,
        public ?bool $isActive,
        public ?string $currentStatus,
        public ?string $currentStatusAt,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $currentStatus = PayloadReader::readArray($payload, 'current_status');

        return new self(
            id: PayloadReader::readRequiredString($payload, 'id'),
            handlerName: PayloadReader::readString($payload, 'handler_name'),
            isActive: PayloadReader::readBool($payload, 'active'),
            currentStatus: PayloadReader::readString($currentStatus, 'status'),
            currentStatusAt: PayloadReader::readString($currentStatus, 'timestamp'),
            raw: $payload,
        );
    }
}
