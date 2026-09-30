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
Client (façade, one method per endpoint, delegates to Action/*; built via Client::create())
  └─ Session (PSR-18 transport, auth, JSON, error mapping)
       └─ PicnicConfig (base URL, api version, agent/device headers, initial token)
Dto/*      readonly entities hydrated via PayloadReader, each keeps ->raw
Search/SearchResultParser   flattens Picnic's PML UI tree into products
Enum/, Exception/
```

Non-obvious behaviors that live in `Session` and must be preserved:

- **Auth token rotates**: `x-picnic-auth` is captured from every response, not only login.
- **Auth errors come as HTTP 200** with `{"error":{"code":"AUTH_ERROR"|"AUTH_INVALID_CRED"}}` and are converted to exceptions.
- **2FA**: login returning `second_factor_authentication_required: true` throws `TwoFactorRequiredException`; `generate2FA`/`verify2FA` may answer 204 or an empty body.
- Picnic requires the `x-picnic-agent` / `x-picnic-did` / okhttp `User-Agent` headers on every request (configurable in `PicnicConfig`).
- Login is lazy in `Client` (first call), `authenticate()` forces it. The secret is `md5(password)`.
- Some endpoints return raw UI trees (`searchProductsRawResponse`, delivery scenario/position, lists) and intentionally stay `array`. Structured endpoints return DTOs.

## Conventions

- `declare(strict_types=1)`, `final` classes, typed signatures, exceptions from `src/Exception`.
- Every endpoint needs a test asserting method + URL + request body, and one for the decoded response. Tests use `php-http/mock-client` (no network), see `tests/Support/AbstractPicnicTestCase.php` and `tests/ClientTest.php`.
- New structured endpoints get a DTO following the `PayloadReader` + `->raw` pattern.

## Reference docs in repo

- `ROADMAP.md` (untracked, agent-oriented backlog with verified endpoint notes and upstream references: `python-picnic-api2`, JS `picnic-api`, `ivo-toby/mcp-picnic`)
- `UPGRADING.md`, `README.md`
