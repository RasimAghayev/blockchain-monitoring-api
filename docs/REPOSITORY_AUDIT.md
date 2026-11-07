# Repository audit

Phase 3 (`auditor`), 2026-11-05. Everything below was read from the real code at HEAD `c2b6907`
(`master`) or verified by a live command (command + result given). Nothing was fixed — this phase is
docs-only. Severity owners point at the Phase 4-11 plan in
`projects/info/analysis/blockchain-monitoring-api.md`.

## P0 — Critical

### P0-1. No authentication or authorization on any CRUD endpoint, or on `check/clear_cache`

**Evidence:**
- `src/be/app/Http/Controllers/Tokens/Router/TokenRouter.php:7` —
  `//    'middleware' => ['api', 'auth:api'],` (commented out; the group carries only `'prefix' =>
  'tokens'`).
- `src/be/app/Http/Controllers/Transactions/Router/TransactionRouter.php:7` — identical pattern.
- Every `$this->authorize(...)` call in `TokenController.php` (lines 111, 191, 277, 371, 520) and
  `TransactionController.php` (lines 107, 187, 273, 358, 424) is commented out.
- `src/be/bootstrap/app.php`'s `api` middleware group (`ThrottleRequests`,
  `EnsureFrontendRequestsAreStateful`, `CompressResponse`) has no auth guard, and is never attached
  to the `v1` route group in `routes/api.php` anyway (that group applies only `'throttle:60,1'`
  directly).
- `src/be/routes/Checks/Router/CheckRouter.php` (via `app/Http/Controllers/Checks/Router/`) applies no
  middleware beyond the global `throttle:60,1` either.

**Impact:** anyone who can reach the container can list/create/update/delete every `Token` and
`Transaction`, and hit `GET /api/v1/check/clear_cache` (runs 7 `Artisan::call()` maintenance
commands — see P1-1) with zero credentials. `laravel/sanctum`, `php-open-source-saver/jwt-auth`, a
`User` model implementing `JWTSubject`, and the `personal_access_tokens` migration are all present and
correctly configured — this is disabled auth, not missing auth infrastructure. The commented lines are
precise and consistent (every single authorize call, both middleware declarations) — this reads as
auth that was deliberately turned off during development and never turned back on.

**Owner:** Phase 9 (hardener) — re-enable `auth:api`/`authorize()` as the primary fix; Phase 4/6 should
not touch this (it's an application-logic change, not git hygiene or a dependency bump).

### P0-2. CI's own security-audit gate is red on the default branch right now — verified live, not just read statically

**Evidence (live GitHub API, run 2026-11-05):**
```
gh api repos/RasimAghayev/blockchain-monitoring-api/actions/runs?branch=master\&event=push
  → 39541729490  conclusion=success  2026-10-21T04:01:12Z
  → 41702488122  conclusion=failure  2026-11-03T19:21:22Z   (current HEAD c2b6907 is on this push)

gh api repos/RasimAghayev/blockchain-monitoring-api/actions/runs/41702488122/jobs
  → "Audit the dependencies": failure
  → "Run the test suite": skipped
  → "Run the linter": skipped
```
**Root cause:** the 8 live Symfony advisories Phase 2 found via `composer audit --locked` (all
transitive via `laravel/framework`, all same-minor patch-fixable — see
`projects/info/deps/blockchain-monitoring-api.md`) fail the CI workflow's `composer audit
--abandoned=report` step (abandoned packages are tolerated; real advisories are not). Because that
step fails before the test/lint steps run, **the test suite has not actually executed in CI since
these advisories appeared** (reported 2026-10-28 → 2026-11-04 per Phase 2's audit timestamps, which
lines up with the gap between the 10-21 green run and the 11-03 red one).

**Impact:** this directly contradicts the repo's own `README.md` CI/CD table, which shows all five
gates (build, install, audit, test, lint) with a ✅. The README is describing the pipeline's intended
shape, not its current, live, verified state. This is the correct Phase 5 baseline fact: whoever runs
Phase 5's "record the real baseline" step must record CI as currently red, not green, and must not
let a stale README table stand uncorrected.

**Owner:** Phase 6 (upgrader) fixes the root cause (bump the 5 affected Symfony packages to their
fixed minor versions); Phase 15 (documenter) corrects the README's CI table once Phase 6 and Phase 10
both close.

### P0-3. Exception messages are leaked to API callers unconditionally, including a test-confirmed trigger

**Evidence:**
- `src/be/app/Helpers/TransactionHelper.php:25-26` —
  `catch (Throwable $e) { return ErrorApiResponse::make('An error occurred: '.$e->getMessage(), 500); }`
  — always includes the raw exception message, with no `config('app.debug')` check. This bypasses the
  debug-gated path in `bootstrap/app.php`'s `withExceptions()` renderer entirely, because the
  exception never reaches that global handler — it's caught inside the controller action first.
- Concrete, test-locked-in trigger: `StoreTokenRequest`/`TokenRequestTrait::getCommonRules()`
  (`src/be/app/Http/Controllers/Tokens/Traits/TokenRequestTrait.php:16-21`) has **no `unique` rule**
  for `tokenAddress`, even though `getCommonMessages()` on the very next block defines a
  `'tokenAddress.unique' => 'This address is already in use.'` message that nothing ever triggers. A
  duplicate `POST /api/v1/tokens` therefore hits the DB's own unique constraint on `tokens.address`
  unvalidated, throws an uncaught `QueryException`, and the test suite itself documents the resulting
  behavior as expected:
  `src/be/tests/Feature/Tokens/TokenCrudTest.php:48-61` (`test_it_rejects_a_duplicate_address`) asserts
  `->assertStatus(500)` — a bug encoded as a passing test, not caught by it.

**Impact:** any uncaught exception on any of the 10 CRUD actions returns the raw PHP/DB exception text
to the caller in every environment, not just when `APP_DEBUG=true`. This can include table/column
names and driver-specific wording. Combined with P0-1 (no auth), this information disclosure is
reachable by anyone.

**Owner:** Phase 9 (hardener) for the debug-gating fix in `TransactionHelper`; Phase 7/11 for adding
the missing `unique:tokens,address` validation rule and rewriting the test to assert 422 instead of
500 (tests-only changes are Phase 11's scope, but the validation rule itself is application code —
Phase 9 or an earlier code-writing phase, per the plan below).

## P1 — High

### P1-1. Unauthenticated endpoint executes 7 privileged maintenance commands on every hit

`CheckController::clearCache()` (`src/be/app/Http/Controllers/Checks/CheckController.php:207-220`)
runs `optimize:clear`, `config:cache`, `config:clear`, `clear-compiled`, `cache:clear`, `route:cache`,
`view:clear` via `Artisan::call()`, reachable at `GET /api/v1/check/clear_cache` with no auth beyond
the blanket `throttle:60,1`. Beyond the auth gap itself (P0-1), this is independently worth flagging:
repeatedly calling `config:cache`/`route:cache` from inside a live web request is unusual (these are
normally deploy-time steps), and `route:cache` serializes a route collection built by this repo's own
runtime filesystem scan (see `docs/ARCHITECTURE.md`'s routing section) — a cheap, repeatable
config/route-cache churn vector once reachable without credentials.

### P1-2. Both written security-header middlewares are dead — zero security headers are actually sent

- `SecurityHeaders` (`src/be/app/Http/Middlewares/SecurityHeaders.php`) sets `X-XSS-Protection`,
  `X-Frame-Options: DENY`, `X-Content-Type-Options`, `Referrer-Policy`, a CSP, `Permissions-Policy`,
  and HSTS — fully written, but commented out of the global middleware stack:
  `src/be/bootstrap/app.php:58` — `//            SecurityHeaders::class,`.
- `ContentSecurityPolicyMiddleware` is registered under a named `'csp'` group
  (`bootstrap/app.php:72-74`) that is never attached to any route anywhere — confirmed by
  `grep -n "'csp'\|SecurityHeaders::class\|->middleware(" src/be/app -r` returning zero matches
  outside the two middleware classes' own definitions.

**Impact:** every response from this API ships with none of the security headers either class was
written to add. This is a complete, ready-to-use fix sitting one line away from working (uncomment
`SecurityHeaders::class` in the global stack, or attach `'csp'` to the `v1` route group) — unusually
cheap for the severity.

### P1-3. 8 live Symfony security advisories, transitive via `laravel/framework` (carried forward from Phase 2, now proven to be actively breaking CI — see P0-2)

Full detail already in `projects/info/deps/blockchain-monitoring-api.md`; not re-derived here. All 5
affected packages have a same-minor patch fix available (no major bump needed).

### P1-4. `laravel/horizon` and `doctrine/dbal` installed with zero wiring/usage (Phase 2 finding, confirmed again this phase by reading the actual code)

`config/horizon.php` does not exist; zero references to `Horizon` in `app/`/`routes/`/
`bootstrap/providers.php`; shipped default `QUEUE_CONNECTION=sync` gives Horizon nothing to monitor.
`doctrine/dbal`: `grep -rn "Doctrine\\\\DBAL\|renameColumn\|dropColumn\|->change()"` across
`app/`/`config/`/`database/migrations/` → no matches.

### P1-5. `predis/predis` vs. the actually-configured Redis client, and Redis's place in the startup graph

`config/database.php:146` defaults `REDIS_CLIENT` to `phpredis`, not the installed `predis/predis`
package; the `redis` PECL extension is installed in none of `php.dockerfile`/`test.dockerfile`/
`composer.dockerfile`. Independently of that mismatch: `docker-compose.yml` has both `nginx` and `php`
`depends_on: redis: condition: service_healthy`, even though Redis sits on no actual code path at the
shipped default config (`CACHE_DRIVER=file`, `QUEUE_CONNECTION=sync`) — a hard startup dependency on a
service the app does not use today.

## P2 — Medium

### P2-1. Zero foreign keys and zero non-unique indexes across all 4 domain tables

`token_holders.token_address`, `dim_table.token_id`, `dim_table.token_address` carry no
`->foreign()` constraint back to `tokens` (confirmed by reading all 4 migration files directly:
`database/migrations/2025_02_18_234531_create_tokens_table.php`,
`..._234532_create_token_holders_table.php`, `..._235432_create_dim_table.php`,
`..._234537_create_transactions_table.php`). Referential integrity is enforced only in PHP
(`TokenObserver`, `TokenController::storeOrUpdateTokenHolders()`), never by the database. No index
exists on any of the documented filterable columns (`from_address`, `to_address`, `token_address`,
`holder_address`) beyond the two unique columns (`tokens.address`, `transactions.hash`).

### P2-2. `DIM` module is a fully-wired, zero-interface, essentially undocumented shadow table

See `docs/ARCHITECTURE.md`'s dedicated section. Summary: synced automatically from every `Token`
write via `TokenObserver`, no controller/route/API surface, no unique constraint on its own
`token_id` (race-condition risk under concurrent writes to the same token), and a dead `casts()`
entry (`order_price`) referencing a field that exists in neither `$fillable` nor the migration — a
copy-paste leftover. `OtherInfo.md`'s entire content is one line naming the module with no further
explanation anywhere in the repo.

### P2-3. Two diverging `.env.example` files

Root `.env.example` is fully customized to this project (`DB_CONNECTION=pgsql`,
`QUEUE_CONNECTION=sync`, Telegram vars, `API_BLOCKCHAIN_URL`) and matches what `docker-compose.yml`
actually mounts (`var/.env`, per the README). `src/be/.env.example` is still the **unmodified stock
Laravel skeleton** (`APP_NAME=Laravel`, `SESSION_DRIVER=database`, `CACHE_STORE=database`,
`QUEUE_CONNECTION=database`, no pgsql/Telegram/blockchain vars at all) — a stale duplicate that would
mislead anyone who copies it instead of the root one.

### P2-4. Dead code tied to documentation/annotation workarounds

- `TokenController::updateA()` (line 459) and `TransactionController::updateA()` (line 522) — both
  empty private no-op methods whose only purpose is to host an `@OA\Put(...)` annotation block; the
  real PUT handling lives in each class's `update()` method (routed via `Route::match(['put',
  'patch'], ...)`).
- `ErrorMethodNotAllowedResponse` (`src/be/app/Http/Responses/ErrorMethodNotAllowedResponse.php`) is
  fully written (405, correct message) but never imported or used anywhere. `bootstrap/app.php:93`
  comments out the corresponding `BadMethodCallException` match arm, with its own comment explaining
  a Pint-tokenizer incompatibility with a commented `use` statement. Net effect: a real `405 Method
  Not Allowed` on this API today falls through to the `default` arm and returns the generic
  `ErrorInternalServerErrorResponse` (500-shaped body) instead of a 405.

### P2-5. Unused MySQL extensions and unused Postgres extensions baked into every image

`php.dockerfile`/`test.dockerfile`/`composer.dockerfile` all `docker-php-ext-install pdo_mysql
mysqli` despite the project exclusively using PostgreSQL (`docker-compose.yml` has no MySQL service).
`postgres.dockerfile` installs `pg_cron`, `postgis`, `timescaledb` — `grep` across
`database/migrations/` and `app/` for any geospatial/cron/time-series usage found none.

### P2-6. Telegram notification sends raw Markdown syntax without `parse_mode`

`TelegramService::sendMessage()` (`src/be/app/Services/Telegram/TelegramService.php:27-34`) posts to
the Telegram Bot API with no `parse_mode` field; `TransactionRepository::prepareTransactionMessage()`
builds the message with `*bold*`/`` `code` ``/link syntax that Telegram would render as literal
characters, not formatting, without that field set.

## P3 — Low

- `TransactionHelper`'s name is misleading (it is a try/catch-to-response mapper, not a DB-transaction
  wrapper) — naming/documentation smell, not a behavior bug.
- `nginx.dockerfile` creates an always-empty `/var/www/html/fe` directory, consistent with the
  already-known orphaned-frontend situation (`node.dockerfile`, Phase 2 finding).
- Three overlapping health signals (`/up`, `/api/v1/check/health`, `/api/v1/check/db`) with no
  liveness/readiness split — see `docs/ARCHITECTURE.md`.
- `COMMAND.md` at repo root describes a different, generic Laravel/Docker setup (services named
  `phpunit`, `pgadmin`, a network named `esds`, `docker-compose` with a hyphen) that does not match
  the real `docker-compose.yml` (services `nginx`/`php`/`composer`/`artisan`/`test`/`lint`/`pgsql`/
  `redis`, network `blockchain`, modern `docker compose` without the hyphen) — a stale, generic
  boilerplate doc left over from an earlier project template, actively misleading if followed as-is.
- `ApiFilter::buildQuery()`'s `'json'` operator branch (`src/be/app/Filters/ApiFilter.php:72`) returns
  `['JSON_CONTAINS', $column, $value]` — the column/operator order is reversed relative to every other
  branch (`[$column, $operator, $value]`). Currently unreachable (`TokenFilters`/`TransactionFilters`
  never include `'json'` in their `safeParms`), so this is latent, not live — but it would produce
  broken SQL the moment a `json` filter is ever enabled.
- CI's branch trigger (`.github/workflows/ci.yml:8-10`, `branches: [master, test]`) will silently stop
  matching the moment Phase 4 renames `master`→`main` unless the same change updates this file — not
  a bug today, a sequencing note for Phase 4's own plan.

## Secret scan (this phase, independently — Docker-based, redacted)

Phase 1 only checked `.env*` filenames; Phase 2 only checked registry-token config files. This phase
ran `gitleaks` itself, twice, via Docker (`zricethezav/gitleaks:latest`, `--redact` throughout, no raw
secret value ever printed to this session):

```
MSYS_NO_PATHCONV=1 docker run --rm -v "<repo>:/repo" zricethezav/gitleaks:latest \
  detect --source=/repo --no-git -v --redact
  → leaks found: 3   (working tree)

MSYS_NO_PATHCONV=1 docker run --rm -v "<repo>:/repo" zricethezav/gitleaks:latest \
  detect --source=/repo -v --redact --log-opts="--all"
  → 47 commits scanned; leaks found: 3   (full history, all local/remote refs)
```

All 3 findings (identical in both runs, same fingerprint, commit `93f1c98a`) are the `generic-api-key`
rule matching the literal fake blockchain address `0x1234567890abcdef1234567890abcdef12345678` used as
test fixture data in `src/be/tests/Feature/Tokens/TokenCrudTest.php:23,37` and
`TokenValidationTest.php:34` — a hex string that trips the rule's entropy heuristic, not a real secret.
**Confirmed clean**: no real credential, key, or token found in the working tree or in history.

## Licence / legal risk

Root `LICENSE` is MIT (`Copyright (c) 2024 Rasim Aghayev`); `src/be/composer.json`'s `license` field
is also `MIT`. Confirms Phase 2's finding that every dependency (93 prod + 47 dev PHP packages, fully
license-parsed) is permissive (MIT/BSD-3-Clause/Apache-2.0, two tri-licensed packages with BSD as the
applicable option). **No legal/licence risk found.**

## Duplication

No duplication found between this repo and any other repo in this program beyond the two coincidental
patterns already noted in Phase 2 (common package names like `guzzlehttp/guzzle`, which every PHP repo
in this program also has, and the `AC-Task` CORS-URL correlation discussed in `docs/ARCHITECTURE.md`).
Within the repo itself: `TokenController`/`TransactionController` and their `*Router`/`*Service`/
`*Repository`/`*Filters` pairs are structurally parallel by design (the same vertical-slice template
applied to two features) — this is intentional symmetry, not unintended duplication, and nothing here
rises to the "same logic in ≥3 places" bar standing decision 7 sets for DRY extraction.
