<?php

declare(strict_types=1);

namespace PhPicnic\Dto;

/**
 * The authenticated Picnic user. Unmapped fields live in {@see $raw}.
 */
final readonly class User
{
    use HydratesFromArray;

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
     * @param array<mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: self::str($data, 'user_id') ?? self::str($data, 'id'),
            firstName: self::str($data, 'firstname') ?? self::str($data, 'first_name'),
            lastName: self::str($data, 'lastname') ?? self::str($data, 'last_name'),
            contactEmail: self::str($data, 'contact_email') ?? self::str($data, 'email'),
            phone: self::str($data, 'phone'),
            raw: $data,
        );
    }
}
