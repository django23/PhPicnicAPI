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

use PhPicnic\Client;
use PhPicnic\Enum\CountryCode;

$picnic = new Client(
    username: 'your@email.here',
    password: 'your-password',
    countryCode: CountryCode::NL, // or 'NL' | 'DE' | 'BE' | 'FR'
);

// Authentication is lazy — it happens on your first call. Call ->login() to do it eagerly.
```

### Two-factor authentication

Modern accounts require a second factor. Login throws `TwoFactorRequiredException`; request a
code and verify it to finish:

```php
use PhPicnic\Enum\TwoFactorChannel;
use PhPicnic\Exception\TwoFactorRequiredException;

try {
    $picnic->login();
} catch (TwoFactorRequiredException $e) {
    $picnic->generate2FA(TwoFactorChannel::SMS); // or 'EMAIL'
    // ...prompt the user for the code they received...
    $picnic->verify2FA('123456');
}
```

### Caching the auth token

Every login round-trips the network. Cache the token and reuse it to skip re-authenticating:

```php
$token = $picnic->login()->getAuthToken();
// ...store $token somewhere...

$picnic = new Client(
    username: 'your@email.here',
    password: 'your-password',
    countryCode: CountryCode::NL,
    authToken: $token, // reused — no login request
);
```

### Searching for a product

Picnic's search now returns a UI tree; the client parses it into `Product` objects. Use
`searchRaw()` if you need the untouched response.

```php
use PhPicnic\Dto\Product;

$products = $picnic->search('coffee'); // list<Product>
foreach ($products as $product) {
    echo $product->name, ' — €', number_format(($product->displayPrice ?? 0) / 100, 2), "\n";
    // $product->id, ->unitQuantity, ->imageId, ->soleArticleId, ->raw (full payload)
}

$raw = $picnic->searchRaw('coffee'); // array — the full PML tree
```

### Check the cart

```php
$cart = $picnic->getCart();          // Cart DTO
$cart->totalPrice;                   // cents
foreach ($cart->items as $item) { /* CartItem */ }
$cart->raw;                          // full payload for anything unmapped
```

### Manipulating the cart

All of these return the updated `Cart`.

```php
$picnic->addProduct('10511523', 2);                       // add 2 of one product
$picnic->addProducts(['10511523' => 2, '20622634' => 1]); // batch add (id => quantity)
$picnic->removeProduct('10511523');                       // remove 1
$picnic->clearCart();                                     // empty the cart
$picnic->setDeliverySlot('slot-id');                      // pick a delivery slot
```

### Deliveries & slots

```php
$picnic->getDeliverySlots();                  // list<DeliverySlot>
$picnic->getCurrentDeliveries();              // list<Delivery> — placed but not yet delivered
$picnic->getDeliveries();                     // list<Delivery> — all (POSTs /deliveries/summary)
$picnic->getDelivery('delivery-id');          // Delivery (now a GET)
$picnic->getDeliveryScenario('delivery-id');  // array — live routing tree
$picnic->getDeliveryPosition('delivery-id');  // array — live driver position / ETA
```

### Lists

```php
$picnic->getList();                       // all lists
$picnic->getList('list-id');              // a single list
$picnic->getSublist('list-id', 'sub-id'); // a sublist
```

### DTOs

Structured responses (`User`, `Cart`, `CartItem`, `Delivery`, `DeliverySlot`, `Product`) are
returned as typed, readonly DTOs. Field shapes drift between Picnic API versions, so hydration
is lenient: known fields are typed (nullable), and the complete payload is always available on
`->raw`. UI-tree endpoints (search-raw, delivery scenario/position, lists) return plain arrays.

### Error handling

```php
use PhPicnic\Exception\AuthenticationException;
use PhPicnic\Exception\PicnicApiException;

try {
    $picnic->getUser();
} catch (AuthenticationException $e) {
    // bad credentials / missing token
} catch (PicnicApiException $e) {
    $e->getMessage();   // human-readable
    $e->statusCode;     // HTTP status
    $e->responseBody;   // raw response body
}
```

## Custom HTTP client

Pass your own PSR-18 client and PSR-17 factories (handy for timeouts, proxies, logging, or tests):

```php
$picnic = new Client(
    username: '...',
    password: '...',
    countryCode: CountryCode::NL,
    httpClient: $myPsr18Client,
    requestFactory: $myPsr17Factory,
    streamFactory: $myPsr17Factory,
);
```

## Development

```shell
composer install
composer test   # PHPUnit
composer stan   # PHPStan (level 8)
composer cs     # php-cs-fixer (dry-run); composer cs-fix to apply
```

## Status

This release modernizes how the client talks to Picnic — current endpoints, required headers,
auth-token rotation, two-factor login — and adds a typed DTO layer. Further integrations
(Symfony/Laravel, recipes, payments) may follow.

## License

MIT.
