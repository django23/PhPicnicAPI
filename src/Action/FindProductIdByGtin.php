<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use InvalidArgumentException;
use PhPicnic\LazyLoginApi;

/**
 * Look up a product id by barcode (GTIN/EAN) through Picnic's public QR redirect. No auth token is sent, redirects are followed by hand, and only https Picnic hosts are contacted. Needs a PSR-18 client that does not follow redirects itself.
 */
final readonly class FindProductIdByGtin
{
    private const int MAX_REDIRECTS = 5;

    /** Picnic redirects unknown barcodes to the storefront landing page. */
    private const string UNKNOWN_GTIN_MARKER = '/link/store/storefront';

    public function __construct(private LazyLoginApi $api)
    {
    }

    /**
     * @throws InvalidArgumentException when the GTIN is malformed
     */
    public function execute(string $gtin): ?string
    {
        if (preg_match('/^\d{8,14}$/', $gtin) !== 1) {
            throw new InvalidArgumentException('A GTIN is 8 to 14 digits.');
        }

        $url = sprintf('https://picnic.app/%s/qr/gtin/%s', strtolower($this->api->location()->countryCode->value), $gtin);

        for ($redirectCount = 0; $redirectCount <= self::MAX_REDIRECTS; ++$redirectCount) {
            $location = $this->api->sendPublicRequest($url)->getHeaderLine('Location');
            if ($location === '') {
                return null;
            }

            $url = str_starts_with($location, '/') ? 'https://picnic.app' . $location : $location;

            if (str_contains($url, self::UNKNOWN_GTIN_MARKER)) {
                return null;
            }

            if (preg_match('/;id=([^;?&#\/]+)/', $url, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }
}
