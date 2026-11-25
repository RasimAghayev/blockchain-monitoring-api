# Changelog

All notable changes to this project are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/); this
project does not yet cut version tags, so changes accumulate under
`[Unreleased]` until the first tagged release.

## [Unreleased]

Phase 9 hardening pass, branch `phase-9/hardening-auth-security`. Full before/after
detail for each item lives in `docs/TECHNICAL_DEBT.md`'s "Resolved" table; this entry
is the Keep-a-Changelog-shaped summary.

### Added
- `auth:api` (JWT) guard re-enabled on `Tokens`/`Transactions` CRUD routes; a full
  `AuthController` (`login`, `me`, `refresh`, `logout`) added — re-enabling the guard
  with no way to obtain a token would have locked out every caller.
- `TokenPolicy` / `TransactionPolicy` registered via `Gate::policy()` in
  `AppServiceProvider::boot()` (required explicitly: both models live outside the
  conventional `App\Models` namespace, which defeats Laravel's automatic policy
  discovery).
- Unique constraint + FK (`onDelete('cascade')`) on `dim_table.token_id`; index on
  `token_holders.token_address` — closes a real race under concurrent writers and an
  unindexed hot-path query, migration
  `2025_02_20_000001_add_missing_constraints_and_indexes.php`.
- `Makefile` at repo root with the full Docker-first target set (`up`, `test`, `lint`,
  `verify`, …) mirroring CI.
- `tests/Feature/Auth/AuthTest.php`, `tests/Feature/AuthorizationTest.php`,
  `tests/Unit/Filters/ApiFilterTest.php` — new coverage for the auth flow, the
  now-enforced 401s across every protected route, and `ApiFilter`'s `json` operator.
- `TestCase::actingAsApiUser()` helper for Feature tests needing an authenticated
  JWT-guard user.
- `GET /v1/health/live` and `GET /v1/health/ready` — liveness/readiness split per
  `docs/adr/0003-liveness-readiness-split.md` (Phase 9's dual-mode/observability
  requirement). Liveness checks nothing; readiness checks DB always and Redis only
  when a driver is actually configured to use it, returning 503 (not 500) on a
  dependency outage. The pre-existing `/up`, `/check/health`, `/check/db` routes are
  unchanged.

### Changed
- PHP runtime bumped `8.4` → `8.6` (all 3 Docker targets), PostgreSQL base image
  bumped `18-alpine` → `19-alpine` (latest stable, standing decision 1), both
  re-pinned to the current digest. `pdo_mysql`/`mysqli` (unused — this project
  is PostgreSQL-only) and `pg_cron`/`postgis`/`timescaledb` (unused — no
  migration or query references any of the three) dropped from the respective
  images. `darkaonline/l5-swagger` `8.6` → `11.4`; npm devDependencies
  (`autoprefixer`, `axios`, `concurrently`, `laravel-vite-plugin`, `postcss`,
  `tailwindcss`, `vite`) bumped to current latest stable; `doctrine/dbal`
  removed (zero usage anywhere in the codebase).
- `REDIS_CLIENT` default changed from `phpredis` to `predis` (`config/database.php`,
  both `.env.example` files, `var/.env`) — no PECL `redis` extension is compiled into
  any image; the pure-PHP client was already a direct dependency and simply never
  configured as the default.
- `routes/api.php` now applies the named `api` middleware group (`throttle:60,1`,
  `EnsureFrontendRequestsAreStateful`, `CompressResponse`) instead of a raw
  `middleware(['throttle:60,1'])` — the group's other two members were defined in
  `bootstrap/app.php` but never actually attached to any route.
- `SecurityHeaders` and `ContentSecurityPolicyMiddleware` were both fully written
  but never attached to any route — zero security headers shipped. Both added to
  the global middleware stack in `bootstrap/app.php`. `SecurityHeaders` no longer
  sets `Content-Security-Policy` itself (it collided with
  `ContentSecurityPolicyMiddleware` — whichever ran second silently won);
  `ContentSecurityPolicyMiddleware` is now the sole owner of that header.
- `TokenController::storeOrUpdateTokenHolders()` now wraps its delete-then-insert
  holder refresh in `DB::transaction()` — previously non-atomic, so a failure between
  the two left a token's holder list empty, and two concurrent refreshes for the same
  address could interleave.
- `TokenRequestTrait` / `TransactionRequestTrait::getCommonRules()` both gained a real
  `Rule::unique(...)->ignore($id)` check (`tokenAddress` / `transactionHash`) instead of
  letting a duplicate insert fall through to an unhandled 500.
- `TelegramService::sendMessage()` now sends `parse_mode: Markdown` with its payload.

### Fixed
- **Every `auth:api`-gated route returned 404 instead of 401 against the real stack**
  for any client that didn't send `Accept: application/json` (e.g. plain `curl`) —
  invisible to the test suite because PHPUnit's `$this->json()` test helper always
  sends that header. Root cause:
  `Illuminate\Auth\Middleware\Authenticate::unauthenticated()` only skips
  `redirectTo()` when `expectsJson()` is true; `redirectTo()` falls through to the
  framework's own unconditional default, `redirectGuestsTo(fn () => route('login'))`
  — and this app, a pure JSON API with no web login route, threw
  `RouteNotFoundException`, which `bootstrap/app.php`'s exception renderer maps to a
  404. Found by smoke-testing the finished auth work against the live
  nginx+php-fpm Docker stack rather than relying on PHPUnit alone. Fixed with
  `redirectGuestsTo(fn () => null)` — this app never redirects a guest anywhere, so
  the redirect target is now always null regardless of `expectsJson()`. Covered by a
  new regression test in `AuthorizationTest` that deliberately omits the JSON Accept
  header.
- **`darkaonline/l5-swagger` 8→11 major bump was not actually functional once verified
  end-to-end** (decision 17: a version bump is not done until the new version's real
  behavior is checked, not just that Composer resolved it). `php artisan
  l5-swagger:generate` failed with `Required @OA\Info() not found` despite a syntactically
  correct `@OA\Info` docblock. Root cause: L5Swagger 11's `Generator::setAnalyser()`
  falls back to an attributes-only `ReflectionAnalyser` when `scanOptions.analyser` is
  unset, silently dropping support for this codebase's classic PHPDoc `@OA\...`
  annotations (swagger-php's own internal default correctly wires both an attribute and
  a docblock factory; L5Swagger's wrapper never falls through to it).
  `config/l5-swagger.php` now explicitly wires both factories. `doctrine/annotations`
  — removed earlier in this same pass as apparently-dead weight — turned out to still
  gate `DocBlockAnnotationFactory::isSupported()` at runtime and was re-added as a
  direct dependency. Verified: `l5-swagger:generate` exits 0 and the generated spec
  lists every real route.
- `app/Http/Controllers/SwaggerController.php` carried a stray
  `@OA\PathItem(path="app")` with no operations, producing a bogus empty `"app": []`
  entry in the generated OpenAPI document (invisible until the fix above let generation
  actually complete). Removed.
- `updateA()` dead methods removed from `TokenController` / `TransactionController`;
  their `@OA\Put` annotations merged into the real `update()` method's existing
  `@OA\Patch` docblock (one route already handles both verbs).
- `bootstrap/app.php`'s exception renderer now maps
  `Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException` (the real 405
  Laravel's router throws, e.g. `DELETE` on a `GET`/`POST`-only route) to
  `ErrorMethodNotAllowedResponse::make()` — the previous, commented-out line matched
  on `BadMethodCallException`, a plain PHP "no such method" error that would never
  have fired as Laravel's 405; real 405s previously fell through to the generic 500
  branch with a misleading message.
- `ApiFilter::buildQuery()`'s `json` operator had a reversed tuple shape; removed (the
  `default` branch already produced the correct shape, making the dedicated branch both
  wrong and redundant).
- `database/seeders/DatabaseSeeder.php` called `TaskSeeder::class`, a class that does
  not exist anywhere in this codebase, fatally erroring `php artisan db:seed`. Removed.
- `App\Http\Controllers\DIM\Models\DIM`'s `casts()` method referenced an
  `order_price` column that has never existed in `dim_table`'s migration. Removed.

### Removed
- `check/clear_cache` endpoint (unauthenticated, flushed the entire application cache)
  per `docs/adr/0002-remove-live-cache-clear-endpoint.md`; replaced by `make
  cache-clear`.
