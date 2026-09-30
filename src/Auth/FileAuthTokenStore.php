<?php

declare(strict_types=1);

namespace PhPicnic\Auth;

use PhPicnic\Contract\AuthTokenStoreInterface;
use PhPicnic\Exception\InvalidConfigurationException;

/**
 * Keeps the token in a file that only the current user can read (0600). Writes
 * go through a temporary file and a rename, so a crash never leaves half a token.
 */
final readonly class FileAuthTokenStore implements AuthTokenStoreInterface
{
    public function __construct(private string $path)
    {
    }

    public function load(): ?string
    {
        if (! is_file($this->path)) {
            return null;
        }

        $authToken = trim((string) file_get_contents($this->path));

        return $authToken === '' ? null : $authToken;
    }

    public function save(string $authToken): void
    {
        $temporaryPath = $this->path . '.' . bin2hex(random_bytes(4)) . '.tmp';

        if (file_put_contents($temporaryPath, $authToken) === false || ! chmod($temporaryPath, 0600)) {
            throw new InvalidConfigurationException(sprintf('Cannot write the auth token to "%s".', $this->path));
        }

        if (! rename($temporaryPath, $this->path)) {
            unlink($temporaryPath);

            throw new InvalidConfigurationException(sprintf('Cannot move the auth token into "%s".', $this->path));
        }
    }
}
