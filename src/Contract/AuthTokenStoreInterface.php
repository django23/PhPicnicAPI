<?php

declare(strict_types=1);

namespace PhPicnic\Contract;

/**
 * Where the rotating auth token is kept between requests and processes. Never
 * store the password here.
 */
interface AuthTokenStoreInterface
{
    public function load(): ?string;

    public function save(string $authToken): void;
}
