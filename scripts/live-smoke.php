<?php

declare(strict_types=1);

/**
 * Live smoke test against the real Picnic API. Read-only: it never changes the cart.
 *
 *   composer smoke              step 1: logs in; if 2FA is needed, sends the SMS and stops
 *   composer smoke -- 123456    step 2: verifies the SMS code, then runs the checks
 *
 * Credentials come from .env. The rotating auth token (also the partial one that
 * waits for the SMS code) is cached in .picnic-token (gitignored), so the SMS step
 * only happens when there is no valid cached token.
 */

use PhPicnic\ApiLocation;
use PhPicnic\Client;
use PhPicnic\Credentials;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\PicnicConfig;

require __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();

$tokenCachePath = __DIR__ . '/../.picnic-token';
$cachedAuthToken = is_file($tokenCachePath) ? trim((string) file_get_contents($tokenCachePath)) : null;

$picnic = Client::create(
    new Credentials($_ENV['PICNIC_USERNAME'], $_ENV['PICNIC_PASSWORD'], $cachedAuthToken ?: null),
    new PicnicConfig(new ApiLocation($_ENV['PICNIC_COUNTRY_CODE'] ?? 'NL', $_ENV['PICNIC_API_VERSION'] ?? '15')),
);

function saveToken(?string $token, string $path): void
{
    if ($token === null) {
        return;
    }

    file_put_contents($path, $token);
    chmod($path, 0600);
}

function step(string $title): void
{
    echo "\n== {$title}\n";
}

$smsCode = $argv[1] ?? null;

if ($smsCode !== null) {
    step('Verify SMS code');
    $picnic->verifyTwoFactorCode($smsCode);
}

try {
    step('Fetch logged-in user');
    $user = $picnic->fetchLoggedInUser();
} catch (TwoFactorRequiredException) {
    step('2FA required: sending SMS');
    $picnic->requestTwoFactorCode('SMS');
    saveToken($picnic->currentAuthToken(), $tokenCachePath);
    echo "\nSMS sent. Now run: composer smoke -- <code>\n";
    exit(0);
}

echo "user id: {$user->userId}, name: " . ($user->firstName ?? '-') . "\n";

$failedChecks = [];

/**
 * @param callable(): string $check returns a one-line result
 */
function check(string $title, callable $check, array &$failedChecks): void
{
    try {
        echo sprintf("%-28s OK    %s\n", $title, $check());
    } catch (PicnicApiException $picnicApiException) {
        $failedChecks[] = $title;
        echo sprintf("%-28s FAIL  %s\n", $title, $picnicApiException->getMessage());
    }
}

echo "\n";
check('products()->search', static function () use ($picnic): string {
    $products = $picnic->products()->search('melk');

    return count($products) . ' products, first: ' . ($products[0]->name ?? '-');
}, $failedChecks);
check('cart()->fetch', static function () use ($picnic): string {
    $cart = $picnic->cart()->fetch();

    return "{$cart->id}: " . count($cart->items) . ' lines, ' . ($cart->totalPrice ?? 0) . ' cents';
}, $failedChecks);
check('deliveries()->fetchAvailableSlots', static fn (): string => count($picnic->deliveries()->fetchAvailableSlots()) . ' slots', $failedChecks);
check('deliveries()->fetchCurrent', static fn (): string => count($picnic->deliveries()->fetchCurrent()) . ' current', $failedChecks);
check('deliveries()->fetchAll', static fn (): string => count($picnic->deliveries()->fetchAll()) . ' deliveries', $failedChecks);
check('shoppingLists()->fetchAll', static fn (): string => count($picnic->shoppingLists()->fetchAll()) . ' top-level nodes', $failedChecks);

saveToken($picnic->currentAuthToken(), $tokenCachePath);
echo "\nAuth token cached in .picnic-token\n";

exit($failedChecks === [] ? 0 : 1);
