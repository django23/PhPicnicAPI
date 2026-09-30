<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Exception\InvalidConfigurationException;

/**
 * Account credentials, plus an optional auth token cached from an earlier
 * session so the client can skip logging in again.
 *
 * Picnic wants an md5 of the password as the login secret. That hash is as
 * sensitive as the password itself: anyone holding it can log in.
 */
final readonly class Credentials
{
    public function __construct(
        public string $username,
        public string $secret,
        public ?string $cachedAuthToken = null,
    ) {
    }

    /**
     * @throws Exception\InvalidConfigurationException when the password is not valid UTF-8
     */
    public static function fromPassword(string $username, string $password, ?string $cachedAuthToken = null): self
    {
        if (! mb_check_encoding($password, 'UTF-8')) {
            throw new InvalidConfigurationException('The password must be valid UTF-8.');
        }

        return new self($username, md5($password), $cachedAuthToken);
    }

    /**
     * For apps that store the md5 secret instead of the plain password.
     */
    public static function fromHashedSecret(string $username, string $md5Secret, ?string $cachedAuthToken = null): self
    {
        return new self($username, $md5Secret, $cachedAuthToken);
    }
}
