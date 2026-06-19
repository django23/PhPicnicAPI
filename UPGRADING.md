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
$picnic = new \PhPicnic\Client($_ENV['username'], $_ENV['password'], $_ENV['country_code']);
```

**After** — everything is explicit; no `$_ENV`, no phpdotenv needed:

```php
use PhPicnic\Client;
use PhPicnic\Enum\CountryCode;

$picnic = new Client(
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
| `getUser()` | array | `Dto\User` |
| `getCart()`, `addProduct()`, `addProducts()`, `removeProduct()`, `clearCart()`, `setDeliverySlot()` | array | `Dto\Cart` |
| `search()` | array (flat) | `list<Dto\Product>` (parsed from the new UI tree) |
| `getDeliverySlots()` | array | `list<Dto\DeliverySlot>` |
| `getDelivery()` | array | `Dto\Delivery` |
| `getDeliveries()`, `getCurrentDeliveries()` | array | `list<Dto\Delivery>` |

DTOs expose typed (nullable) fields plus a `->raw` array with the complete payload. UI-tree
endpoints (`searchRaw()`, `getDeliveryScenario()`, `getDeliveryPosition()`, `getList()`,
`getSublist()`) still return arrays.

### Endpoint corrections (Picnic changed these)

- `client_id` is now `30100`, and `x-picnic-agent` / `x-picnic-did` headers are sent.
- `search()` calls `/pages/search-page-results` (the old flat `/search` was removed).
- `getDelivery()` is now a **GET**.
- `getDeliveries()` / `getCurrentDeliveries()` POST to `/deliveries/summary`.
- The auth token rotates and is refreshed from every response.
- Auth failures returned as HTTP 200 error bodies now raise `AuthenticationException`.

### New methods

`addProducts()` (batch), `setDeliverySlot()`, `getSublist()`, `getDeliveryScenario()`,
`getDeliveryPosition()`, and the 2FA methods below.

### Exceptions

Failures now throw a typed hierarchy instead of a bare `\Exception('Something went wrong')`:

- `PhPicnic\Exception\PicnicException` — base type.
- `PhPicnic\Exception\AuthenticationException` — login failed / no auth token returned.
- `PhPicnic\Exception\PicnicApiException` — non-2xx response; carries `->statusCode` and `->responseBody`.

### Behavioral fixes

- `clearCart()` now works (v1 threw because of a missing argument).
- `getCurrentDeliveries()` posts a consistent `["CURRENT"]` filter to `/deliveries`.
- The auth token is captured correctly as a string and reused across requests via a single client.
- Authentication is **lazy** (on first call); call `->login()` to force it, and
  `->getAuthToken()` to cache and reuse the token via the `authToken:` constructor argument.

### Two-factor authentication (now supported)

Login throws `TwoFactorRequiredException` for 2FA accounts; call `generate2FA(channel)` then
`verify2FA(code)` to complete it. See the README for an example.
