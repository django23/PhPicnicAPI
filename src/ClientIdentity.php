<?php

declare(strict_types=1);

namespace PhPicnic;

/**
 * How this client presents itself to Picnic. Picnic requires these values on
 * every request, so the defaults mimic the current Android app.
 */
final readonly class ClientIdentity
{
    /**
     * @param int    $clientId       Picnic client id sent at login
     * @param string $userAgent      HTTP User-Agent header
     * @param string $picnicAgent    x-picnic-agent header (client + app version)
     * @param string $picnicDeviceId x-picnic-did header (device id)
     */
    public function __construct(
        public int $clientId = 30100,
        public string $userAgent = 'okhttp/4.9.0',
        public string $picnicAgent = '30100;1.206.1-#15408',
        public string $picnicDeviceId = '598F770380CA54B6',
    ) {
    }
}
