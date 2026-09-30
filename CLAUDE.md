# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Unofficial, framework-agnostic PHP client (`django23/php-picnic-api`, namespace `PhPicnic\`) for the Picnic supermarket mobile API. Library only, no app. PHP >= 8.4, transport via any PSR-18 client (auto-discovered with `php-http/discovery`, no bundled HTTP client). Nobody else depends on it, so breaking changes are fine.

## Commands

```shell
composer install
composer check                         # everything below, in order (what CI runs)
composer fix                           # apply Rector + php-cs-fixer
composer test                          # phpunit (failOnWarning/failOnRisky are on)
vendor/bin/phpunit --filter testName   # single test
composer analyse                       # phpstan level max + strict/phpunit/deprecation rules
composer lint                          # php-cs-fixer dry-run; `composer lint:fix` applies
composer refactor                      # rector dry-run; `composer refactor:fix` applies
```

CI (`.github/workflows/ci.yml`) runs `composer check` on PHP 8.4 and 8.5 after `composer update`. Definition of done is `composer check` green.

## Architecture

```
Client (built via Client::create(Credentials, PicnicConfig, ?HttpTransport))
  ├─ Resource/*   cart, checkout, products, categories, deliveries, payments, account, consents,
  │               customerService, pages, recipes, mealPlan, userDefinedRecipes: the autocomplete surface
  │    └─ Action/*   one class per endpoint, execute()
  │         └─ LazyLoginApi (logs in on first authenticated call; public/static calls never log in)
  │              └─ Session (facade, <200 lines)
  │                   └─ Http/*  LoginFlow, AuthResponseGuard, RequestBuilder, RequestSender,
  │                              AuthTokenHolder (+ AuthTokenStoreInterface), FailedResponseMapper
  │                   HttpTransport (PSR-18/17), JsonResponseDecoder, ApiErrorBody
  │                   PicnicConfig = ApiLocation + ClientIdentity(AppProfile) + AuthTokenStoreInterface
Enum/ApiEndpoint   every API path lives here, never inline; Enum/PageId for /pages/{id}
Dto/*      readonly entities via PayloadReader (ids required, MalformedResponseException); UiTree for UI trees, RscPage for RSC pages
Recipe/*   value objects for multi-part recipe input (max 3 parameters per method)
```

Non-obvious behaviors that live in `Session` and must be preserved:

- **Agent gating**: Picnic gates endpoints on `x-picnic-agent` and grows the set over time (`/cart` since Aug 2026). Send the identity headers on every API call.
- **RSC**: with newer agents three pages return `text/x-component`; the decoder throws `UnexpectedResponseFormatException`, `fetchRscPage()` parses them into `RscPage`.

- **Auth token rotates**: `x-picnic-auth` is captured from every response, not only login.
- **Auth errors come as HTTP 200** with `{"error":{"code":"AUTH_ERROR"|"AUTH_INVALID_CRED"}}` and are converted to exceptions.
- **2FA**: login returning `second_factor_authentication_required: true` throws `TwoFactorRequiredException`; `requestTwoFactorCode`/`verifyTwoFactorCode` may answer 204 or an empty body.
- Picnic requires the `x-picnic-agent` / `x-picnic-did` / okhttp `User-Agent` headers on every request (configurable through `ClientIdentity` in `PicnicConfig`).
- Login is lazy in `Client` (first call), `authenticate()` forces it. The secret is `md5(password)`.
- Some endpoints return raw UI trees (`searchProductsRawResponse`, delivery scenario/position) and intentionally stay `array`. Structured endpoints return DTOs.

## Conventions

- `declare(strict_types=1)`, `final` classes, typed signatures, exceptions from `src/Exception`.
- Every endpoint needs a test asserting method + URL + request body, and one for the decoded response. Tests use `php-http/mock-client` (no network), see `tests/Support/AbstractPicnicTestCase.php` and `tests/ClientTest.php`.
- New endpoint = an `ApiEndpoint` case (never an inline path), an `Action/*` class, a method on the matching `Resource/*`, and a test asserting method, URL, body and the `x-picnic-agent`/`-did`/`-auth` headers.
- Never auto-retry POST/PUT: a failed cart mutation may have applied (`PicnicApiException::mayHaveChangedState()`).
- Never accept absolute URLs in `Session`, and never send the token to a public/static request.
- Live canary: `composer smoke` (read-only; `-- --profile=V1_206_1`, `-- --write` for an add/remove round trip). Run it after every Picnic-facing change.
- New structured endpoints get a DTO following the `PayloadReader` + `->raw` pattern.

## Local docs

`docs/` (gitignored, local): `CODING_STANDARDS.md` (rules, Clean Code, what each tool enforces), `ARCHITECTURE.md` (layers, add-an-endpoint checklist), `TOOLING.md`, `MAINTENANCE.md`, `HISTORY.md` (what was done and why). Read `CODING_STANDARDS.md` before writing code.

## Staying current

Picnic breaks its private API every one to three months. Once a month, and whenever `composer smoke` fails, follow "Keeping up with Picnic" in `README.md`: compare with MRVDH/picnic-api, python-picnic-api2, mcp-picnic and the Home Assistant integration, then update the "Last checked" table there.

## Reference docs in repo

- `ROADMAP.md` (untracked, agent-oriented backlog with verified endpoint notes and upstream references: `python-picnic-api2`, JS `picnic-api`, `ivo-toby/mcp-picnic`)
- `UPGRADING.md`, `README.md`
