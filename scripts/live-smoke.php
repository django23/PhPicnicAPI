<?php

declare(strict_types=1);

/**
 * Live smoke test against the real Picnic API. Read-only by default.
 *
 *   composer smoke                          step 1: logs in; if 2FA is needed, sends the SMS and stops
 *   composer smoke -- 123456                step 2: verifies the SMS code, then runs the checks
 *   composer smoke -- --profile=V1_206_1    run the checks as another app version (see Enum\AppProfile)
 *   composer smoke -- --write               also add and remove one product to prove the cart mutations
 *
 * Credentials come from .env. The rotating auth token (also the partial one that
 * waits for the SMS code) lives in .picnic-token (gitignored, mode 0600), so the
 * SMS step only happens when there is no valid cached token.
 *
 * Never checked here, on purpose: logout, checkout, payment, order confirmation,
 * delivery cancel and rating, recipe changes, consents, onboarding.
 */

use PhPicnic\ApiLocation;
use PhPicnic\Auth\FileAuthTokenStore;
use PhPicnic\Client;
use PhPicnic\ClientIdentity;
use PhPicnic\Credentials;
use PhPicnic\Enum\AppProfile;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\Exception\UnexpectedResponseFormatException;
use PhPicnic\PicnicConfig;

require __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();

$profile = AppProfile::V1_246_1;
$smsCode = null;
$allowWrites = false;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--profile=')) {
        $profile = constant(AppProfile::class . '::' . substr($argument, 10));
    } elseif ($argument === '--write') {
        $allowWrites = true;
    } else {
        $smsCode = $argument;
    }
}

$tokenStore = new FileAuthTokenStore(__DIR__ . '/../.picnic-token');
$identity = ClientIdentity::forProfile($profile);
$picnic = Client::create(
    Credentials::fromPassword($_ENV['PICNIC_USERNAME'], $_ENV['PICNIC_PASSWORD']),
    new PicnicConfig(
        new ApiLocation($_ENV['PICNIC_COUNTRY_CODE'] ?? 'NL', $_ENV['PICNIC_API_VERSION'] ?? '15'),
        $identity,
        $tokenStore,
    ),
);

echo "Profile: {$profile->name} ({$profile->agentString()})\n";

if ($smsCode !== null) {
    echo "\n== Verify SMS code\n";
    $picnic->verifyTwoFactorCode($smsCode);
}

try {
    echo "\n== Fetch logged-in user\n";
    $user = $picnic->fetchLoggedInUser();
} catch (TwoFactorRequiredException) {
    echo "\n== 2FA required: sending SMS\n";
    $picnic->requestTwoFactorCode('SMS');
    echo "\nSMS sent. Now run: composer smoke -- <code>\n";
    exit(0);
}

echo "user id: {$user->userId}, name: " . ($user->firstName ?? '-') . "\n\n";

$failedChecks = [];

/**
 * @param callable(): string $check returns a one-line result
 */
function check(string $title, callable $check, array &$failedChecks, ?int $expectedHttpStatus = null): void
{
    try {
        echo sprintf("%-46s OK      %s\n", $title, $check());
    } catch (PicnicApiException $picnicApiException) {
        if ($expectedHttpStatus === $picnicApiException->statusCode) {
            echo sprintf("%-46s EXPECT  HTTP %d\n", $title, $expectedHttpStatus);

            return;
        }

        $failedChecks[] = $title;
        echo sprintf("%-46s FAIL    %s\n", $title, $picnicApiException->getMessage());
    } catch (UnexpectedResponseFormatException $unexpectedResponseFormatException) {
        $failedChecks[] = $title;
        echo sprintf("%-46s FORMAT  %s\n", $title, $unexpectedResponseFormatException->contentType);
    }
}

$count = static fn (array $items): string => count($items) . ' items';

check('products()->search', static function () use ($picnic): string {
    $products = $picnic->products()->search('melk');

    return count($products) . ' products, first: ' . ($products[0]->name ?? '-');
}, $failedChecks);
check('products()->suggest', static fn (): string => $count($picnic->products()->suggest('melk')), $failedChecks);
check('products()->fetchDetailsPage', static function () use ($picnic): string {
    $productId = $picnic->products()->search('melk')[0]->id ?? '';

    return $productId . ': ' . implode(',', array_keys($picnic->products()->fetchDetailsPage($productId)));
}, $failedChecks);
check('products()->fetchImage', static function () use ($picnic): string {
    $imageId = $picnic->products()->search('melk')[0]->raw['image_id'] ?? $picnic->products()->search('melk')[0]->raw['imageId'] ?? null;
    if (! is_string($imageId)) {
        return 'no image id on the first product';
    }

    return strlen($picnic->products()->fetchImage($imageId)) . ' bytes';
}, $failedChecks);
check('cart()->fetch', static function () use ($picnic): string {
    $cart = $picnic->cart()->fetch();

    return "{$cart->id}: " . count($cart->items) . ' lines, ' . ($cart->totalPrice ?? 0) . ' cents, mts ' . ($cart->modificationTimestamp ?? '-');
}, $failedChecks);
check('cart()->fetchMinimumOrderValue', static fn (): string => $picnic->cart()->fetchMinimumOrderValue()->minimumOrderValueInCents . ' cents', $failedChecks, 500);
check('deliveries()->fetchAvailableSlots', static fn (): string => count($picnic->deliveries()->fetchAvailableSlots()) . ' slots', $failedChecks);
check('deliveries()->fetchCurrent', static fn (): string => count($picnic->deliveries()->fetchCurrent()) . ' current', $failedChecks);
check('deliveries()->fetchAll', static fn (): string => count($picnic->deliveries()->fetchAll()) . ' deliveries', $failedChecks);
check('deliveries()->fetchById', static function () use ($picnic): string {
    $delivery = $picnic->deliveries()->fetchAll()[0] ?? null;

    return $delivery === null ? 'no deliveries' : $picnic->deliveries()->fetchById($delivery->deliveryId)->status ?? '-';
}, $failedChecks);
check('deliveries()->fetchReceiptPage', static function () use ($picnic): string {
    $delivery = $picnic->deliveries()->fetchAll()[0] ?? null;

    return $delivery === null ? 'no deliveries' : implode(',', array_keys($picnic->deliveries()->fetchReceiptPage($delivery->deliveryId)));
}, $failedChecks);
check('payments()->fetchProfile', static fn (): string => implode(',', array_slice(array_keys($picnic->payments()->fetchProfile()), 0, 4)), $failedChecks);
check('payments()->fetchWalletTransactions', static fn (): string => count($picnic->payments()->fetchWalletTransactions()) . ' transactions', $failedChecks);
check('payments()->fetchWalletTransactionDetails', static function () use ($picnic): string {
    $transaction = $picnic->payments()->fetchWalletTransactions()[0] ?? null;

    return $transaction === null ? 'no transactions' : implode(',', array_slice(array_keys($picnic->payments()->fetchWalletTransactionDetails($transaction->id)), 0, 4));
}, $failedChecks);
check('account()->fetchInfo', static fn (): string => count($picnic->account()->fetchInfo()->featureToggles) . ' feature toggles', $failedChecks);
check('account()->fetchProfileMenu', static fn (): string => implode(',', array_keys($picnic->account()->fetchProfileMenu())), $failedChecks);
check('account()->checkForUpdates', static fn (): string => $picnic->account()->checkForUpdates()->isUpdateRequired ? 'update required' : 'up to date', $failedChecks);
check('customerService()->fetchContactInfo', static fn (): string => implode(',', array_keys($picnic->customerService()->fetchContactInfo())), $failedChecks);
check('customerService()->fetchPublicContactInfo', static fn (): string => implode(',', array_keys($picnic->customerService()->fetchPublicContactInfo())), $failedChecks);
check('customerService()->fetchMessages', static fn (): string => implode(',', array_keys($picnic->customerService()->fetchMessages())), $failedChecks);
check('customerService()->fetchReminders', static fn (): string => implode(',', array_keys($picnic->customerService()->fetchReminders())), $failedChecks);
check('customerService()->fetchParcels', static fn (): string => $count($picnic->customerService()->fetchParcels()), $failedChecks);
check('consents()->fetchSettings', static fn (): string => $count($picnic->consents()->fetchSettings()), $failedChecks);
check('consents()->fetchGeneral', static fn (): string => implode(',', array_keys($picnic->consents()->fetchGeneral())), $failedChecks);
check('pages()->fetchBootstrap', static fn (): string => count($picnic->pages()->fetchBootstrap()['tabs'] ?? []) . ' tabs', $failedChecks);
check('pages()->fetchHome', static fn (): string => implode(',', array_keys($picnic->pages()->fetchHome())), $failedChecks);
check('pages()->fetchPurchases', static fn (): string => implode(',', array_keys($picnic->pages()->fetchPurchases())), $failedChecks);
check('pages()->fetchSlotSelector', static fn (): string => implode(',', array_keys($picnic->pages()->fetchSlotSelector())), $failedChecks);
check('pages()->fetchParcelsOverview', static fn (): string => implode(',', array_keys($picnic->pages()->fetchParcelsOverview())), $failedChecks);
check('pages()->fetchEmptySearch', static fn (): string => implode(',', array_keys($picnic->pages()->fetchEmptySearch())), $failedChecks);
check('pages()->fetchFaq', static fn (): string => implode(',', array_keys($picnic->pages()->fetchFaq())), $failedChecks);
check('pages()->fetchSearchEmptyState', static fn (): string => implode(',', array_keys($picnic->pages()->fetchSearchEmptyState())), $failedChecks);
check('pages()->fetchCategoryTree (RSC)', static fn (): string => count($picnic->pages()->fetchCategoryTree()->rows) . ' rows', $failedChecks);
check('pages()->fetchProfile (RSC)', static fn (): string => count($picnic->pages()->fetchProfile()->rows) . ' rows', $failedChecks);
check('recipes()->fetchCookbook', static fn (): string => implode(',', array_keys($picnic->recipes()->fetchCookbook())), $failedChecks);
check('recipes()->fetchMealPlan', static fn (): string => implode(',', array_keys($picnic->recipes()->fetchMealPlan())), $failedChecks);

if ($allowWrites) {
    echo "\n== Write round trip (--write)\n";
    check('cart()->addProduct', static function () use ($picnic): string {
        $productId = $picnic->products()->search('melk')[0]->id ?? '';

        return count($picnic->cart()->addProduct($productId)->items) . ' lines after adding ' . $productId;
    }, $failedChecks);
    check('cart()->removeProduct', static function () use ($picnic): string {
        $productId = $picnic->products()->search('melk')[0]->id ?? '';

        return count($picnic->cart()->removeProduct($productId)->items) . ' lines after removing ' . $productId;
    }, $failedChecks);
    check('cart()->empty', static fn (): string => count($picnic->cart()->empty()->items) . ' lines after emptying', $failedChecks);
}

echo "\nAuth token cached in .picnic-token\n";

exit($failedChecks === [] ? 0 : 1);
