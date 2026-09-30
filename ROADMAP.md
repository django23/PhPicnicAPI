# PhPicnicAPI Roadmap

This roadmap is written so that **AI agents (or humans) can pick up any single item and
implement it in isolation**. Each item is self-contained: a goal, why it matters, an
upstream reference to copy behavior from, concrete acceptance criteria (including tests),
and a rough difficulty.

## How to work an item

1. Read the existing code first — it's small:
   - `src/Client.php` — public façade, one method per endpoint.
   - `src/Session.php` — PSR-18 transport, auth, JSON encode/decode, error handling.
   - `src/PicnicConfig.php` — base URL + auth-token config.
   - `src/Enum/CountryCode.php`, `src/Exception/*`.
   - `tests/` — every endpoint is tested against `php-http/mock-client` (no network). Copy
     the patterns in `tests/ClientTest.php` and `tests/Support/PicnicTestCase.php`.
2. Match the existing style: `declare(strict_types=1)`, `final` classes, typed signatures,
   `array<mixed>` returns (until the DTO item below lands), exceptions from `src/Exception`.
3. Every new endpoint needs: a typed method on `Client`, a test asserting **method + URL +
   request body**, and a test for the **decoded response**.
4. Definition of done for any item: `composer test`, `composer stan`, `composer cs` all green.

## Upstream references

- Python (maintained fork): `python-picnic-api2` — https://pypi.org/project/python-picnic-api2/
- Python (original): https://github.com/MikeBrink/python-picnic-api
- JS client: `picnic-api` on npm — https://www.npmjs.com/package/picnic-api
- MCP server (good capability map): https://github.com/ivo-toby/mcp-picnic
- Picnic API uses version `15` by default; some clients use `17`. The version is configurable
  via `PicnicConfig`/the `apiVersion:` constructor argument.

---

## Verified API behavior (cross-checked June 2026)

Confirmed against the maintained Python lib (`codesalatdev/python-picnic-api`, updated 2026-06)
and the JS lib (`MRVDH/picnic-api`). These are already implemented — listed here as the
authoritative reference when adding more endpoints:

- **Login:** `POST /user/login` with `{key, secret: md5(password), client_id: 30100}`. Requires
  headers `x-picnic-agent: 30100;1.206.1-#15408`, `x-picnic-did: 598F770380CA54B6`,
  `User-Agent: okhttp/4.9.0`. (All configurable via `PicnicConfig`.)
- **Auth token rotates** — capture `x-picnic-auth` from *every* response, not just login.
- **Auth errors arrive as HTTP 200** with `{"error":{"code":"AUTH_ERROR"|"AUTH_INVALID_CRED"}}`.
- **2FA:** login returns `second_factor_authentication_required: true`; then
  `POST /user/2fa/generate {channel}` and `POST /user/2fa/verify {otp}` (may answer 204/empty).
- **Search:** `GET /pages/search-page-results?search_term=` returns a PML tree; products are the
  `SELLING_UNIT_TILE` nodes' `sellingUnit` payloads (see `Search/SearchResultParser`).
- **Cart:** `POST /cart/add_product {product_id,count}`; batch `POST /cart/products/add` with a
  `{ "<productId>": <qty> }` map; `POST /cart/remove_product`; `POST /cart/clear`;
  `POST /cart/set_delivery_slot {slot_id}`.
- **Deliveries:** `GET /deliveries/{id}` (was POST); `POST /deliveries/summary` with `[]` or
  `["CURRENT"]` (the unsummarized `/deliveries` was removed); `GET /deliveries/{id}/scenario`
  and `/position` for live tracking.
- **Lists:** `GET /lists`, `GET /lists/{id}`, `GET /lists/{id}?sublist={sublistId}`.
- **Removed by Picnic:** `get_categories` (`/my_store`) — no longer functional.
- **Not yet wrapped (good next items):** `GET /articles/{id}/category`, article details via
  `/pages/product-details-page-root?id=`, find-by-GTIN/EAN (barcode) which redirects through
  `https://picnic.app/{cc}/qr/gtin/{ean}`.

---

## Epic 1 — Authentication: two-factor (2FA) — ✅ DONE
Implemented: `Client::generate2FA(channel)` / `verify2FA(code)`, `TwoFactorRequiredException`
(detected from `second_factor_authentication_required`), and `TwoFactorException` for verify
failures. See `tests/TwoFactorTest.php`.

## Epic 2 — Symfony integration (bundle)
**Goal:** First-class Symfony usage via DI.
**Why:** User wants to drop the client into a Symfony app with autowiring.
**Approach:** Add a `PhPicnicBundle` with a `services.yaml` registering `Client` as a service,
a `Configuration` for credentials/country/api version, and binding Symfony's `symfony/http-client`
(PSR-18) automatically. Document `config/packages/ph_picnic.yaml`.
**Acceptance:** A functional test booting a minimal kernel resolves `Client` from the container
with config-driven credentials. Bundle docs in `docs/symfony.md`.
**Difficulty:** Medium. Keep it a separate sub-namespace (`PhPicnic\Bridge\Symfony`) so the core
stays framework-free.

## Epic 3 — Laravel integration (package)
**Goal:** First-class Laravel usage.
**Why:** User wants Laravel support alongside Symfony.
**Approach:** A `PhPicnicServiceProvider` binding `Client` as a singleton from
`config/ph-picnic.php` (publishable), plus an optional `Picnic` facade. Reuse Laravel's bundled
Guzzle as the PSR-18 client.
**Acceptance:** Orchestra Testbench tests resolving `Client` and the facade. Docs in `docs/laravel.md`.
**Difficulty:** Medium. Sub-namespace `PhPicnic\Bridge\Laravel`.

## Epic 4 — Recipes & meal planning
**Goal:** Browse recipes, get details/ingredients, save/unsave, add ingredients to cart.
**Reference:** MCP server "Recipe & Meal Planning" tools; `picnic-api` recipe endpoints.
**Approach:** New methods on `Client`: `getRecipes(?string $category)`, `getRecipe(string $id)`,
`getRecipeIngredients(string $id)`, `saveRecipe(string $id)`, `unsaveRecipe(string $id)`,
`addRecipeToCart(string $id)`. Consider a `Resource\Recipes` sub-object if `Client` gets large.
**Acceptance:** Per-method URL/body/response tests.
**Difficulty:** Medium.

## Epic 5 — Payments & wallet
**Goal:** Payment methods, paginated wallet transactions, transaction details.
**Reference:** MCP "Payment & Financial": payment methods, wallet history (paginated), tx detail.
**Approach:** `getPaymentMethods()`, `getWalletTransactions(int $page = 0)`,
`getWalletTransaction(string $id)`. Handle pagination params.
**Acceptance:** Tests including a paginated request asserting the page query param.
**Difficulty:** Medium.

## Epic 6 — Order status & live delivery tracking — 🟡 PARTIAL
Done: `getDeliveryScenario()` and `getDeliveryPosition()` (return raw UI trees).
**Remaining:** `cancelDelivery(deliveryId)`, `rateDelivery(deliveryId, rating)`,
`sendDeliveryInvoiceEmail(deliveryId)`, and cart checkout/order status
(`GET /cart/checkout/order/{orderId}/status`, confirm). Reference: JS `delivery`/`cart` services.
**Difficulty:** Medium.

## Epic 7 — MGM (refer-a-friend) & reminders
**Goal:** Refer-a-friend (member-get-member) info and delivery reminders.
**Approach:** `getMgmDetails()`; reminder endpoints per upstream. Smaller, good "starter" items.
**Acceptance:** Per-method tests.
**Difficulty:** Low.

## Epic 8 — Typed response DTOs — ✅ DONE (core entities)
Implemented under `src/Dto/`: `User`, `Cart`, `CartItem`, `Delivery`, `DeliverySlot`, `Product`,
all readonly with lenient `fromArray()` hydration and a `->raw` escape hatch. UI-tree endpoints
(search-raw, delivery scenario/position, lists) still return arrays by design.
**Remaining:** add DTOs for any new structured endpoints (e.g. payments/wallet, order status)
as those epics land, following the same `HydratesFromArray` + `->raw` pattern.

## Epic 9 — API version 17 support
**Goal:** Support API version 17 and document payload differences vs 15.
**Approach:** `apiVersion` is already configurable. Audit endpoints whose request/response shape
differs between 15 and 17; add version-aware handling where needed and a compatibility note.
**Acceptance:** Tests parameterized over `apiVersion` where behavior differs.
**Difficulty:** Medium (mostly investigation).

---

## Cross-cutting / nice-to-have
- **Retry & rate-limit handling** in `Session` (honor `Retry-After`, exponential backoff).
- **PSR-3 logging** hook (optional logger injected into `Session`).
- **Code coverage gate** in CI (e.g. fail under a threshold).
- **`CHANGELOG.md`** following Keep a Changelog.
- **Mutation testing** (Infection) for the core transport/auth logic.


---

## Status 2026-09-30: endpoint coverage and agent procedure

Everything in the JS (`MRVDH/picnic-api` v4.10) and Python (`python-picnic-api2`) clients is now
wrapped, except: GET `/lists` (removed by Picnic, 404 on API 15 and 17), `get_categories`
(`/my_store`, removed), `recipe-details-page-root` (removed, replaced by selling groups), and
the L3 category page id (none exists; L3 is a query on the L2 page).

Live matrix (`composer smoke`, read-only) passes 36/36 on `AppProfile::V1_246_1` (the default since
2026-09-30) and 34/36 on `V1_206_1` (the two RSC pages report "format" there, as designed). The
`--write` round trip (add, remove, empty on an empty cart) passes on `V1_246_1`. Not yet verified
live: checkout, payment, order confirm, delivery cancel and rating, recipes, consents, onboarding.

### How to bump the app profile

1. Get the newest Picnic Android APK, decompile (`jadx app.apk -d app-src --show-bad-code --deobf`),
   read `versionName` and `versionCode` from `AndroidManifest.xml`.
2. Confirm `pc:clid` (30100) in the JWT of a fresh login.
3. Add the case to `Enum\AppProfile`, run `composer smoke -- --profile=<CASE>` and `--write`.
4. Only flip the default in `ClientIdentity` once every endpoint passes, including the `--write` round trip.
   Keep the APK out of git.

### Open questions (unverified upstream too)

- Whether the server checks that `x-picnic-did` equals the token's `pc:did`.
- Token lifetime, rate limits, whether `Accept: application/json` changes the RSC pages.
- The exact order of checkout confirm versus status polling.
