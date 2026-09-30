<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Enum\AppProfile;
use PhPicnic\Exception\InvalidConfigurationException;

/**
 * How this client presents itself to Picnic. Picnic requires these values on
 * every request. Pick an app version with {@see forProfile()} or pass a custom
 * agent string; the device id can be fixed, or generated once per install and
 * persisted by the caller.
 */
final readonly class ClientIdentity
{
    public const string SHARED_DEVICE_ID = '598F770380CA54B6';

    /**
     * @param int    $clientId       Picnic client id sent at login (30100 = Android)
     * @param string $userAgent      HTTP User-Agent header
     * @param string $picnicAgent    x-picnic-agent header (client id + app version)
     * @param string $picnicDeviceId x-picnic-did header: 16 uppercase hex characters
     */
    public function __construct(
        public int $clientId = 30100,
        public string $userAgent = 'okhttp/4.9.0',
        public string $picnicAgent = AppProfile::V1_246_1->value,
        public string $picnicDeviceId = self::SHARED_DEVICE_ID,
    ) {
        if (preg_match('/^[0-9A-F]{16}$/', $picnicDeviceId) !== 1) {
            throw new InvalidConfigurationException('The device id must be 16 uppercase hex characters.');
        }
    }

    public static function forProfile(AppProfile $profile, ?string $deviceId = null): self
    {
        return new self(picnicAgent: $profile->agentString(), picnicDeviceId: $deviceId ?? self::SHARED_DEVICE_ID);
    }

    public function withProfile(AppProfile $profile): self
    {
        return new self($this->clientId, $this->userAgent, $profile->agentString(), $this->picnicDeviceId);
    }

    public function withDeviceId(string $deviceId): self
    {
        return new self($this->clientId, $this->userAgent, $this->picnicAgent, $deviceId);
    }

    /**
     * A fresh random device id. Persist it (with the auth token) so the same
     * install keeps the same device id.
     */
    public function withGeneratedDeviceId(): self
    {
        return $this->withDeviceId(strtoupper(bin2hex(random_bytes(8))));
    }
}
