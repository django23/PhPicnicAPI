# PhPicnic-API

[![CI](https://github.com/django23/PhPicnicAPI/actions/workflows/ci.yml/badge.svg)](https://github.com/django23/PhPicnicAPI/actions/workflows/ci.yml)
[![Buy me a book](https://img.shields.io/badge/buy%20me%20a%20coffee-donate-yellow.svg)](https://www.buymeacoffee.com/djangoboy)

Inspired by and ported from the Python version: https://github.com/MikeBrink/python-picnic-api
(see also the actively maintained [`python-picnic-api2`](https://pypi.org/project/python-picnic-api2/)).

Unofficial, **framework-agnostic** PHP wrapper for the Picnic API. While not all API methods
are implemented yet, you'll find most of what you need to build a working application.

This library is not affiliated with Picnic and retrieves data from the endpoints of the
mobile application. **Use at your own risk.**

> **v2.0 is a breaking release** — see [`UPGRADING.md`](UPGRADING.md). It targets **PHP 8.4+**,
> is fully typed, ships a test suite, and talks to any [PSR-18](https://www.php-fig.org/psr/psr-18/)
> HTTP client instead of bundling Guzzle.

## Requirements

- PHP **8.4** or newer
- Any PSR-18 HTTP client + PSR-17 factories (auto-discovered via `php-http/discovery`)

## Installation

```shell
composer require django23/php-picnic-api
```

You also need a concrete PSR-18 client. If you don't already have one, Guzzle works out of the box:

```shell
composer require guzzlehttp/guzzle
```

In Symfony, `symfony/http-client` is discovered automatically; in Laravel, Guzzle is already present.

## Usage

```php
<?php

require 'vendor/autoload.php';

use PhPicnic\ApiLocation;
use PhPicnic\Client;
use PhPicnic\Credentials;
use PhPicnic\Enum\CountryCode;
use PhPicnic\PicnicConfig;

$picnic = Client::create(
    Credentials::fromPassword('your@email.here', 'your-password'),
    new PicnicConfig(new ApiLocation(CountryCode::NL)), // optional: NL (default), BE, DE, FR
);

// Authentication is lazy: it happens on your first call. Call ->authenticate() to do it eagerly.
```

Everything is grouped by area, so autocomplete lists what you can do:

| Accessor | What it covers |
| --- | --- |
| `$picnic->fetchLoggedInUser()` | the account, plus `authenticate()`, 2FA and `currentAuthToken()` |
| `cart()` | fetch, add/remove products and recipe groups, empty, pick a delivery slot, minimum order value |
| `checkout()` | start, initiate payment, poll, cancel, confirm the order |
| `products()` | search, suggestions, product details page, barcode (GTIN) lookup, image URLs and downloads |
| `categories()` | category pages, level 1 to 3 |
| `deliveries()` | slots, current and past deliveries, live tracking, receipt, rating, cancel, resend invoice |
| `payments()` | payment profile, wallet transactions |
| `account()` | user info, profile menu, update check, phone verification, push, onboarding, logout |
| `consents()` | read and save privacy consents |
| `customerService()` | contact info, in-app messages, reminders, external parcels |
| `pages()` | bootstrap, any Fusion or RSC page by id, deeplinks, FAQ |
| `recipes()` | cookbook, meal plan, save, basket, user-defined recipes, notes, images |

### Two-factor authentication

Modern accounts require a second factor. Login throws `TwoFactorRequiredException`; request a
code and verify it to finish:

```php
use PhPicnic\Enum\TwoFactorChannel;
use PhPicnic\Exception\TwoFactorRequiredException;

try {
    $picnic->authenticate();
} catch (TwoFactorRequiredException) {
    $picnic->requestTwoFactorCode(TwoFactorChannel::SMS); // or 'EMAIL'
    // ...prompt the user for the code they received...
    $picnic->verifyTwoFactorCode('123456');
}
```

In a web app the SMS and the code arrive in different requests. Use a persistent token store
(next section) so the half-authenticated token survives between them.

### Keeping the auth token

The token rotates on every response. Give the client a store and it saves each new token, and
starts from the stored one, so you skip the login (and the SMS):

```php
use PhPicnic\Auth\FileAuthTokenStore;

$picnic = Client::create(
    Credentials::fromPassword('your@email.here', 'your-password'),
    new PicnicConfig(tokenStore: new FileAuthTokenStore('/var/app/.picnic-token')), // file mode 0600
);
```

Implement `PhPicnic\Contract\AuthTokenStoreInterface` for a database or cache. The store only ever
holds the token, never the password. If you would rather not hold the plain password either, use
`Credentials::fromHashedSecret($username, $md5)` (the hash is as sensitive as the password).

### Choosing the app version (agent)

Picnic serves different formats depending on the `x-picnic-agent` header, so you choose it. The
default is `AppProfile::V1_246_1`, live-verified on every endpoint. It serves three pages
(`category-tree-root`, `profile-root`, `promo-group-deep-dive`) as React Server Components; the
older `V1_206_1` serves them as JSON:

```php
use PhPicnic\ClientIdentity;
use PhPicnic\Enum\AppProfile;

$picnic = Client::create(
    $credentials,
    new PicnicConfig(identity: ClientIdentity::forProfile(AppProfile::V1_206_1)->withGeneratedDeviceId()),
);

// Or switch for a single request:
$profileJson = $picnic->pages()->fetchPage('profile-root', identityOverride: ClientIdentity::forProfile(AppProfile::V1_206_1)); // array
$profilePage = $picnic->pages()->fetchProfile(); // RscPage, with the default profile
```

`fetchPage()` throws `UnexpectedResponseFormatException` when Picnic answers with RSC, and
`fetchRscPage()` when it answers with JSON. The client never switches agent on its own. A custom
agent string works too: `new ClientIdentity(picnicAgent: '30100;1.246.1-15599;')`.
`withGeneratedDeviceId()` creates a random per-install device id: persist it if you use it.

### Search, products and barcodes

```php
$products = $picnic->products()->search('coffee');            // list<Product>
$picnic->products()->suggest('cof');                          // list<SearchSuggestion>
$picnic->products()->fetchDetailsPage($products[0]->id);      // array: the product page tree
$picnic->products()->findIdByGtin('8712345678901');           // ?string, e.g. "s1234567"
$picnic->products()->imageUrl($imageId, ImageSize::LARGE);    // string
$picnic->products()->fetchImage($imageId);                    // PNG bytes
```

Barcode lookup follows Picnic's public QR redirect by hand: it sends no token, only contacts
`picnic.app` and `picnicinternational.com` over https, and needs a PSR-18 client that does not
follow redirects itself.

### The cart and checkout

```php
$cart = $picnic->cart()->fetch();                  // Cart DTO
$picnic->cart()->addProduct('s1010146', 2);
$picnic->cart()->addMultipleProducts(['s1010146' => 2, 's1002939' => 1]);
$picnic->cart()->removeProduct('s1010146');
$picnic->cart()->selectDeliverySlot('slot-id');
$picnic->cart()->empty();

// Placing an order:
$start = $picnic->checkout()->start($cart->modificationTimestamp);   // may throw CheckoutIssueException
$payment = $picnic->checkout()->initiatePayment($start->orderId, 'myapp://payment-return');
// send the customer to $payment->redirectUrl, then poll:
$status = $picnic->checkout()->fetchTransactionStatus($payment->transactionId);
if ($status->isFinished()) {
    $picnic->checkout()->confirmOrder($start->orderId);
}
```

`CheckoutIssueException` carries the issue type (`isAgeVerificationRequired()`) and a
`resolveKey`: start the checkout again with it once the issue is resolved.

### Deliveries, payments, account and more

```php
$picnic->deliveries()->fetchCurrent();                // list<Delivery>
$picnic->deliveries()->fetchDriverPosition($id);      // live tracking tree
$picnic->deliveries()->rate($id, 9);
$picnic->payments()->fetchWalletTransactions(1);      // list<WalletTransaction>
$picnic->account()->fetchInfo();                      // UserInfo
$picnic->customerService()->fetchParcels();           // list<Parcel>
$picnic->pages()->fetchBootstrap();                   // tabs and the page ids behind them
$picnic->recipes()->fetchCookbook();
```

Endpoints that return Picnic's UI trees (pages, receipts, tracking, most recipe calls) return
plain arrays; structured ones return DTOs.

### DTOs

Structured responses are typed, readonly DTOs. Picnic's field shapes drift, so hydration is
lenient: ids are required (a missing one throws `MalformedResponseException`), other known
fields are nullable, and the complete payload is always available on `->raw`.

### Error handling

```php
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;

try {
    $picnic->cart()->addProduct('s1010146');
} catch (InvalidCredentialsException) {
    // bad credentials, expired token
} catch (PicnicApiException $e) {
    $e->statusCode;             // HTTP status, 0 when the request never reached Picnic
    $e->responseBody;
    $e->mayHaveChangedState();  // true for a failed POST/PUT
}
```

**Never retry a failed cart change blindly.** Picnic applies the change and renders the cart in
the same request, so a non-2xx answer can still mean the change happened. Read the cart first.

## Custom HTTP client

Pass your own PSR-18 client and PSR-17 factories (handy for timeouts, proxies, logging, or tests):

```php
use PhPicnic\HttpTransport;

$picnic = Client::create(
    Credentials::fromPassword('...', '...'),
    transport: new HttpTransport($myPsr18Client, $myPsr17Factory, $myPsr17Factory),
);
```

**Set timeouts on your HTTP client.** PSR-18 has no timeout option and this library sets none.
With Guzzle: `new GuzzleHttp\Client(['connect_timeout' => 5, 'timeout' => 15])`.

## Security notes

- Only relative paths reach the API, so the auth token can never be sent to another host.
- `baseUrlOverride` must be https (plain http only for localhost) and cannot carry credentials.
- The token store writes files with mode 0600 through a temporary file and a rename.
- The library never logs. If you add logging, redact `x-picnic-auth`, `secret`, `key` and `otp`.
- Picnic changes its API every one to three months; the live smoke test (`composer smoke`) is the canary.

## Keeping up with Picnic (instructions for AI agents)

Picnic changes its private API without notice, roughly **every one to three months** (agent
gating, removed pages, new response formats). This library only stays useful if someone compares
it with the community clients on a schedule. If you are an AI agent working in this repo, do this
**once a month**, and immediately whenever `composer smoke` fails:

1. Run `composer smoke` (read-only). Any FAIL or FORMAT line means Picnic changed something.
2. Compare with the repos below, newest commits and releases first, and update the table at the
   end of this section.
3. Apply what you find, keep `composer check` green, then rerun `composer smoke -- --write`.

| Repo | Look at | What to look for |
| --- | --- | --- |
| [MRVDH/picnic-api](https://github.com/MRVDH/picnic-api) (JS, most active) | releases, `src/http-client.ts`, `src/domains/*/service.ts`, `types/` | **new default `x-picnic-agent`** (add an `Enum\AppProfile` case), new or removed endpoints, pages that changed format (RSC), new checkout or error codes |
| [codesalatdev/python-picnic-api](https://github.com/codesalatdev/python-picnic-api) (PyPI `python-picnic-api2`, used by Home Assistant) | `client.py`, `session.py`, releases, open issues | headers newly required per endpoint (cart got gated in Aug 2026), removed endpoints, fixes for `KeyError` on changed payloads |
| [ivo-toby/mcp-picnic](https://github.com/ivo-toby/mcp-picnic) | releases, `.minispec/knowledge/decisions/` | 2FA and login edge cases, why tools were dropped, mutation behaviour notes |
| [home-assistant/core `picnic`](https://github.com/home-assistant/core/tree/dev/homeassistant/components/picnic) and its issues | issues labelled `integration: picnic` | real-world breakage reports, often days before the libraries react |

What to do with a finding:

- **New agent version:** add an `Enum\AppProfile` case, run `composer smoke -- --profile=<CASE>` and
  `--write`, and only make it the default in `ClientIdentity` when every check passes. The steps to
  find `versionName` and `versionCode` are in `ROADMAP.md`.
- **New endpoint:** add an `ApiEndpoint` case, an `Action/*` class, a `Resource/*` method and a test
  that asserts method, URL, body and the identity headers. Read the exact shape from the other
  repos' source rather than guessing, and mark anything you could not verify live in the docblock.
- **Removed endpoint:** confirm it 404s live, then delete it and note it in `UPGRADING.md`.
- **Changed payload:** keep DTO fields nullable, keep `->raw`, and add a fixture test.
- Never guess. If the repos disagree or you cannot verify a behaviour, say so in the docblock or
  in `ROADMAP.md` under "Open questions".

Last checked (update this table every time):

| Date | Repo state compared | Result |
| --- | --- | --- |
| 2026-09-30 | MRVDH/picnic-api v4.10.0 (`9352e19`), python-picnic-api2 2.0.1 (`17139d0`), mcp-picnic v1.15.1 | All endpoints ported except removed ones (`/lists`, categories, `recipe-details-page-root`). Default agent 1.246.1. `composer smoke`: 36/36 reads, write round trip OK. |

## Development

```shell
composer install
composer check   # validate, lint, rector (dry-run), phpstan (max), phpunit
composer fix     # apply Rector and php-cs-fixer
composer smoke   # live, read-only check against the real API (needs .env, asks for an SMS code)
```

## Status

This release modernizes how the client talks to Picnic — current endpoints, required headers,
auth-token rotation, two-factor login — and adds a typed DTO layer. Further integrations
(Symfony/Laravel, recipes, payments) may follow.

## License

MIT.
