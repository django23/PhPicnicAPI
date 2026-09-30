<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * The authenticated Picnic user. Unmapped fields live in {@see $raw}.
 */
final readonly class User
{
    /**
     * @param array<mixed> $raw
     */
    public function __construct(
        public ?string $userId,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $contactEmail,
        public ?string $phone,
        public array $raw,
    ) {
    }

    /**
     * @param array<mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            userId: PayloadReader::readString($payload, 'user_id') ?? PayloadReader::readString($payload, 'id'),
            firstName: PayloadReader::readString($payload, 'firstname') ?? PayloadReader::readString($payload, 'first_name'),
            lastName: PayloadReader::readString($payload, 'lastname') ?? PayloadReader::readString($payload, 'last_name'),
            contactEmail: PayloadReader::readString($payload, 'contact_email') ?? PayloadReader::readString($payload, 'email'),
            phone: PayloadReader::readString($payload, 'phone'),
            raw: $payload,
        );
    }
}
