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
        public string $userId,
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
            userId: PayloadReader::readRequiredString($payload, 'user_id', 'id'),
            firstName: PayloadReader::readFirstString($payload, 'firstname', 'first_name'),
            lastName: PayloadReader::readFirstString($payload, 'lastname', 'last_name'),
            contactEmail: PayloadReader::readFirstString($payload, 'contact_email', 'email'),
            phone: PayloadReader::readString($payload, 'phone'),
            raw: $payload,
        );
    }
}
