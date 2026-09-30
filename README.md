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

$picnic = Client::create(
    username: 'your@email.here',
    password: 'your-password',
    countryCode: CountryCode::NL, // or 'NL' | 'DE' | 'BE' | 'FR'
);

// Authentication is lazy — it happens on your first call. Call ->authenticate() to do it eagerly.
```

### Two-factor authentication

Modern accounts require a second factor. Login throws `TwoFactorRequiredException`; request a
code and verify it to finish:

```php
use PhPicnic\Enum\TwoFactorChannel;
use PhPicnic\Exception\TwoFactorRequiredException;

try {
    $picnic->authenticate();
} catch (TwoFactorRequiredException $e) {
    $picnic->requestTwoFactorCode(TwoFactorChannel::SMS); // or 'EMAIL'
    // ...prompt the user for the code they received...
    $picnic->verifyTwoFactorCode('123456');
}
```

### Caching the auth token

Every login round-trips the network. Cache the token and reuse it to skip re-authenticating:

```php
$token = $picnic->authenticate()->currentAuthToken();
// ...store $token somewhere...

$picnic = Client::create(
    username: 'your@email.here',
    password: 'your-password',
    countryCode: CountryCode::NL,
    authToken: $token, // reused — no login request
);
```

### Searching for a product

Picnic's search now returns a UI tree; the client parses it into `Product` objects. Use
`searchProductsRawResponse()` if you need the untouched response.

```php
use PhPicnic\Dto\Product;

$products = $picnic->searchProductsByTerm('coffee'); // list<Product>
foreach ($products as $product) {
    echo $product->name, ' — €', number_format(($product->displayPrice ?? 0) / 100, 2), "\n";
    // $product->id, ->unitQuantity, ->imageId, ->soleArticleId, ->raw (full payload)
}

$raw = $picnic->searchProductsRawResponse('coffee'); // array — the full PML tree
```

### Check the cart

```php
$cart = $picnic->fetchShoppingCart();          // Cart DTO
$cart->totalPrice;                   // cents
foreach ($cart->items as $item) { /* CartItem */ }
$cart->raw;                          // full payload for anything unmapped
```

### Manipulating the cart

All of these return the updated `Cart`.

```php
$picnic->addProductToCart('10511523', 2);                       // add 2 of one product
$picnic->addMultipleProductsToCart(['10511523' => 2, '20622634' => 1]); // batch add (id => quantity)
$picnic->removeProductFromCart('10511523');                       // remove 1
$picnic->emptyShoppingCart();                                     // empty the cart
$picnic->selectDeliverySlotForCart('slot-id');                      // pick a delivery slot
```

### Deliveries & slots

```php
$picnic->fetchAvailableDeliverySlots();                  // list<DeliverySlot>
$picnic->fetchCurrentDeliveries();              // list<Delivery> — placed but not yet delivered
$picnic->fetchAllDeliveries();                     // list<Delivery> — all (POSTs /deliveries/summary)
$picnic->fetchDeliveryById('delivery-id');          // Delivery (now a GET)
$picnic->fetchDeliveryRoutingScenario('delivery-id');  // array — live routing tree
$picnic->fetchDeliveryDriverPosition('delivery-id');  // array — live driver position / ETA
```

### Lists

```php
$picnic->fetchAllShoppingLists();                       // all lists
$picnic->fetchShoppingListById('list-id');              // a single list
$picnic->fetchShoppingListSublist('list-id', 'sub-id'); // a sublist
```

### DTOs

Structured responses (`User`, `Cart`, `CartItem`, `Delivery`, `DeliverySlot`, `Product`) are
returned as typed, readonly DTOs. Field shapes drift between Picnic API versions, so hydration
is lenient: known fields are typed (nullable), and the complete payload is always available on
`->raw`. UI-tree endpoints (search-raw, delivery scenario/position, lists) return plain arrays.

### Error handling

```php
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;

try {
    $picnic->fetchLoggedInUser();
} catch (InvalidCredentialsException $e) {
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
$picnic = Client::create(
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
