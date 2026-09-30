# Upgrading

## v1 → v2.0

v2.0 is a ground-up modernization. The public surface is intentionally broken to give the
library a typed, framework-agnostic foundation.

### Requirements

- **PHP 8.4+** is now required (was effectively PHP 7).
- The library no longer depends on Guzzle or `vlucas/phpdotenv` directly. It depends on
  [PSR-18](https://www.php-fig.org/psr/psr-18/) abstractions and discovers an installed
  client via `php-http/discovery`. **Install a concrete client** (e.g. `composer require guzzlehttp/guzzle`).

### Constructor & configuration

**Before** — credentials plus a hard dependency on `$_ENV` (`base_url`, `api_version`,
`country_code` had to be loaded via phpdotenv, and the `$countryCode` argument was actually ignored):

```php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$picnic = \PhPicnic\Client::create($_ENV['username'], $_ENV['password'], $_ENV['country_code']);
```

**After** — everything is explicit; no `$_ENV`, no phpdotenv needed:

```php
use PhPicnic\Client;
use PhPicnic\Enum\CountryCode;

$picnic = Client::create(
    username: 'your@email.here',
    password: 'your-password',
    countryCode: CountryCode::NL, // or 'NL' | 'DE' | 'BE' | 'FR'
    apiVersion: '15',             // optional
);
```

### Return types (now typed DTOs)

Structured endpoints return readonly DTOs instead of raw arrays:

| Method | v1 return | v2 return |
| --- | --- | --- |
| `fetchLoggedInUser()` | array | `Dto\User` |
| `getCart()`, `addProduct()`, `addMultipleProductsToCart()`, `removeProduct()`, `clearCart()`, `selectDeliverySlotForCart()` | array | `Dto\Cart` |
| `searchProductsByTerm()` | array (flat) | `list<Dto\Product>` (parsed from the new UI tree) |
| `fetchAvailableDeliverySlots()` | array | `list<Dto\DeliverySlot>` |
| `fetchDeliveryById()` | array | `Dto\Delivery` |
| `getDeliveries()`, `getCurrentDeliveries()` | array | `list<Dto\Delivery>` |

DTOs expose typed (nullable) fields plus a `->raw` array with the complete payload. UI-tree
endpoints (`searchProductsRawResponse()`, `fetchDeliveryRoutingScenario()`, `fetchDeliveryDriverPosition()`, `fetchAllShoppingLists()` / `fetchShoppingListById()`,
`fetchShoppingListSublist()`) still return arrays.

### Endpoint corrections (Picnic changed these)

- `client_id` is now `30100`, and `x-picnic-agent` / `x-picnic-did` headers are sent.
- `searchProductsByTerm()` calls `/pages/search-page-results` (the old flat `/search` was removed).
- `fetchDeliveryById()` is now a **GET**.
- `getDeliveries()` / `getCurrentDeliveries()` POST to `/deliveries/summary`.
- The auth token rotates and is refreshed from every response.
- Auth failures returned as HTTP 200 error bodies now raise `InvalidCredentialsException`.

### New methods

`addMultipleProductsToCart()` (batch), `selectDeliverySlotForCart()`, `fetchShoppingListSublist()`, `fetchDeliveryRoutingScenario()`,
`fetchDeliveryDriverPosition()`, and the 2FA methods below.

### Exceptions

Failures now throw a typed hierarchy instead of a bare `\Exception('Something went wrong')`:

- `PhPicnic\Exception\UnsupportedCountryException` — base type.
- `PhPicnic\Exception\InvalidCredentialsException` — login failed / no auth token returned.
- `PhPicnic\Exception\PicnicApiException` — non-2xx response; carries `->statusCode` and `->responseBody`.

### Behavioral fixes

- `clearCart()` now works (v1 threw because of a missing argument).
- `getCurrentDeliveries()` posts a consistent `["CURRENT"]` filter to `/deliveries`.
- The auth token is captured correctly as a string and reused across requests via a single client.
- Authentication is **lazy** (on first call); call `->authenticate()` to force it, and
  `->currentAuthToken()` to cache and reuse the token via the `authToken:` constructor argument.

### Two-factor authentication (now supported)

Login throws `TwoFactorRequiredException` for 2FA accounts; call `requestTwoFactorCode(channel)` then
`verifyTwoFactorCode(code)` to complete it. See the README for an example.

## Method and class renames (naming pass)

Names now say what they do. `new Client(...)` became `Client::create(...)` (it auto-discovers the PSR-18/17 dependencies).

| Old | New |
|---|---|
| `getUser` | `fetchLoggedInUser` |
| `search` / `searchRaw` | `searchProductsByTerm` / `searchProductsRawResponse` |
| `getCart` / `clearCart` | `fetchShoppingCart` / `emptyShoppingCart` |
| `addProduct` / `addProducts` / `removeProduct` | `addProductToCart` / `addMultipleProductsToCart` / `removeProductFromCart` |
| `setDeliverySlot` / `getDeliverySlots` | `selectDeliverySlotForCart` / `fetchAvailableDeliverySlots` |
| `getList` | `fetchAllShoppingLists` / `fetchShoppingListById` |
| `getDelivery` / `getDeliveries` / `getCurrentDeliveries` | `fetchDeliveryById` / `fetchAllDeliveries` / `fetchCurrentDeliveries` |
| `login` / `generate2FA` / `verify2FA` / `getAuthToken` | `authenticate` / `requestTwoFactorCode` / `verifyTwoFactorCode` / `currentAuthToken` |

Exceptions: `PicnicException` is now `AbstractPicnicException`, `AuthenticationException` is `AbstractAuthenticationException`. Bad credentials throw `InvalidCredentialsException`, unknown countries throw `UnsupportedCountryException`.
