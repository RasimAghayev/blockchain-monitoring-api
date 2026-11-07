# Architecture

Phase 3 (`auditor`), 2026-11-05. Written from the real code in `projects/repos/blockchain-monitoring-api`
at HEAD `c2b6907` (`master`, clean). Builds on Phase 2's `projects/info/deps/blockchain-monitoring-api.md`
(dependency inventory — not repeated here). This phase is docs-only: nothing below was fixed, only
documented.

## What this is

A PHP/Laravel API that tracks ERC20-style tokens and the on-chain transactions of addresses it is
told to watch. Confirmed by reading the actual domain code (not the repo name): two real resources,
`Token` and `Transaction`, a background job that pulls an address's latest transactions from an
external blockchain API and stores/updates them, and a Telegram notification fired the first time a
given transaction hash is seen. There is no wallet, no signing, no on-chain write path anywhere —
this is a read/cache/alert layer over an external blockchain data provider, not a blockchain node or
a wallet service.

## Request path

```
client → nginx (80/443, public entry point)
            │ fastcgi_pass php:9000
            ▼
          php-fpm → Laravel (api/v1/*, throttle:60,1 global)
            │
            ├── TokenController / TransactionController
            │     → *Service → *Repository → Eloquent → PostgreSQL
            │     (Token writes also fan out to TokenObserver → DIM, see below)
            │
            ├── BlockchainService ──HTTP──→ external blockchain API (API_BLOCKCHAIN_URL)
            │     (token info / top holders, 10-minute cache via CachedApiCallHelper)
            │
            └── CheckController (db / health / static / ip / clear_cache)
```

Routing is unconventional and worth documenting precisely: `routes/api.php` does not list routes
itself. It recursively scans `app/Http/Controllers/**/Router/*.php` at boot time and `require`s every
file it finds under a `Router` subdirectory, all wrapped in one `Route::group(['middleware' =>
['throttle:60,1'], 'prefix' => 'v1'])`. Adding an endpoint means dropping a new `*Router.php` file in
the right `Router/` folder — `routes/api.php` itself is never touched. This works but has two real
costs: (1) `php artisan route:cache` serializes a route collection built by directory traversal, which
is fragile if ever revisited by someone assuming static route files, and (2) there is no single file
a reviewer can read to see "every route in this API" — they have to know to look for `Router/`
directories. Three router files exist today: `TokenRouter.php`, `TransactionRouter.php`,
`CheckRouter.php`.

## Authentication and authorization — present in the dependency graph, absent at runtime

This is the single most important architectural fact about this repo, so it is stated here plainly
and backed by file:line evidence (full severity write-up in `docs/REPOSITORY_AUDIT.md`):

- `laravel/sanctum`, `php-open-source-saver/jwt-auth`, a `User` model implementing `JWTSubject`, the
  `personal_access_tokens` migration, `config/jwt.php`, `config/sanctum.php` — all present and
  correctly configured as infrastructure.
- `TokenRouter.php:7` and `TransactionRouter.php:7` both have
  `//    'middleware' => ['api', 'auth:api'],` — commented out. The route groups carry only
  `'prefix'`.
- Every `$this->authorize(...)` call inside `TokenController` and `TransactionController` (index,
  store, show, update, destroy — all of them, both controllers) is commented out.
- `bootstrap/app.php`'s `api` middleware group is `[ThrottleRequests::class.':60,1',
  EnsureFrontendRequestsAreStateful::class, CompressResponse::class]` — no auth guard in the group
  either, and the group is never attached to the `v1` prefix group in `routes/api.php` (that group
  only applies `'throttle:60,1'` directly, not the named `api` group).

Net effect: every CRUD endpoint for both resources, and the unauthenticated `check/clear_cache`
endpoint (which runs seven `Artisan::call()` maintenance commands), is reachable by anyone who can
reach the container, with no credential of any kind. The JWT/Sanctum machinery is fully wired at the
config level and fully disconnected at the route level. This reads as authentication that was
deliberately disabled during development (the commented lines are precise, not accidental deletions)
and never re-enabled — not as code that was never written.

## Layering

Each feature (`Tokens`, `Transactions`) follows the same vertical-slice shape:
`Controller → Service (interface + impl) → Repository (interface + impl) → Eloquent Model`, with
`Request`/`DTO`/`Resource`/`Filter` classes per feature and bindings registered in
`AppServiceProvider::register()`. This is a real, consistently-applied Clean-Architecture-flavored
layering — proportionate for CRUD-plus-one-external-integration, not over-built. `ApiFilter` is a
shared base class with a per-feature `safeParms`/`columnMap` whitelist (`TokenFilters`,
`TransactionFilters`), which is the right way to expose filterable columns without permitting
arbitrary column injection from query strings.

Two cross-cutting helpers wrap every controller action:
- `TransactionHelper::handleWithTransaction()` — despite the name, this does **not** open a database
  transaction. It is a try/catch that maps domain exceptions (`ValidationException`,
  `AuthorizationException`, `ModelNotFoundException`/`NotFoundHttpException`, anything else) onto the
  envelope response shape. The actual `DB::transaction()` wrapping for writes happens separately,
  inside each `*Service`'s `createX`/`updateX`/`deleteX` methods. The name is misleading; the
  behavior is correct for reads (no transaction needed) and correct for writes (the real transaction
  lives one layer down).
- `CachedApiCallHelper::cachedApiCall()` — caches the external API's status+body (not the
  `JsonResponse` object itself — a prior bug, fixed and documented in the helper's own comment) for a
  caller-specified number of minutes, used by `getTokenInfo`/`getTopHolders`.

Every response, success or error, is wrapped by `ApiBaseResponse`/`SuccessApiResponse`/
`ApiErrorResponse` into the single envelope shape documented in `docs/api-contract.md`
(`timestamp`/`path`/`method`/`error`/`result`). This is a deliberate, consistently-applied
cross-cutting response contract, not ad hoc per-controller formatting.

## The `DIM` module — present, wired, undocumented

`app/Http/Controllers/DIM/Models/DIM.php` (table `dim_table`) is kept in sync with every `Token`
create/update/delete via `TokenObserver` (registered in `AppServiceProvider::boot()`): a denormalized
mirror of each token's address/name/symbol/total_supply, keyed by `token_id`. There is no controller,
no route, no API surface, and no seeder/factory usage for `DIM` anywhere in the tree. The only
documentation of its purpose anywhere in the repo is the single line in `OtherInfo.md`: "DIM - Data
Integration Module (Məlumatların İnteqrasiyası Modulu)". The model's `casts()` method declares a cast
for `order_price`, a field that exists in neither `$fillable` nor the `dim_table` migration — a
leftover from copy-pasted scaffolding (harmless at runtime: the cast simply never applies to anything,
since the attribute never exists). `dim_table.token_id` carries no unique constraint, so the
observer's `updateOrCreate(['token_id' => ...], ...)` is not race-safe under concurrent writes to the
same token. Whether `DIM` is meant to become a real feature or is a dead experiment is a product
decision, not something this phase can resolve from the code alone — flagged for the roadmap.

## Integrations

| Integration | Direction | Config | Behavior when unconfigured |
|---|---|---|---|
| External blockchain API | outbound, `BlockchainService`/`FetchTransactions` job | `API_BLOCKCHAIN_URL` (`config/services.php`) | `isConfigured()` gates every call; returns 503 / logs and no-ops rather than throwing |
| Telegram Bot API | outbound, `TelegramService`, fired from `TransactionRepository::notifyAbout()` on a newly-created transaction | `TELEGRAM_BOT_TOKEN`/`TELEGRAM_CHAT_ID` | `isConfigured()` gates the call; silently skipped otherwise. Message text uses Markdown syntax (`*bold*`, `` `code` ``, link syntax) but `sendMessage()` never sets `parse_mode`, so Telegram would render the raw markup literally, not formatted. |
| `ipapi.co` (third-party IP geolocation) | outbound, `CheckController::getIP()` | none (hardcoded URL) | No gating, no cache, no timeout override — every unauthenticated hit to `/check/ip` makes a live call to a free third-party API |

No other `RasimAghayev` repo is called over HTTP by this one, and this repo publishes nothing another
repo in this program consumes (confirmed in Phase 2). One CORS entry in `config/cors.php:23` whitelists
a Project IDX / Firebase-Studio cloud-workstation hostname containing the substring `idx-ac-task`. This
phase cross-checked the shared channel (`projects/info/analysis/AC-Task.md`, not `AC-Task`'s own
clone/worktree, per this task's isolation rule) and found that **`AC-Task`'s own `config/cors.php`
carries the identical class of finding** — its own Phase-3 audit independently flags "hardcoded IDX
cloud-workstation URL in `config/cors.php`" as a P2 item in that repo too. Two independent repos each
carrying a leftover IDX-workspace preview URL in their CORS allow-list is a strong signal that this is
a **shared development-environment artifact** (both apps were worked on inside Project IDX / Firebase
Studio cloud workstations, which mint one such URL per workspace session), not a deliberate, functional
API contract between the two services — but this phase cannot fully confirm it without reading
`AC-Task`'s actual `cors.php` value, which the isolation rule for this task does not permit. Treated as
**corroborated-but-not-proven**: most likely dev-environment leftover, flagged rather than asserted.

## Database layer

PostgreSQL, 4 domain tables plus Laravel's own scaffolding (`users`, `cache`, `jobs`,
`personal_access_tokens`):

| Table | Columns (beyond id/timestamps) | Constraints | Indexes beyond PK |
|---|---|---|---|
| `tokens` | `address`, `name`, `symbol`, `total_supply` | `address` unique | none |
| `transactions` | `hash`, `from_address`, `to_address`, `amount`, `gas_fee` | `hash` unique | none |
| `token_holders` | `token_address`, `holder_address`, `balance`, `percentage` | none | none |
| `dim_table` | `token_id` (nullable), `token_address`, `token_name`, `token_symbol`, `token_total_supply` (all nullable) | none | none |

Observations, all confirmed by reading the migration files directly (`database/migrations/*.php`),
not inferred:

- **Zero foreign keys anywhere.** `token_holders.token_address` and `dim_table.token_id` /
  `dim_table.token_address` are plain strings/integers with no `->foreign()` constraint back to
  `tokens`. Referential integrity between a token and its holders, or a token and its `DIM` mirror, is
  enforced only in PHP (`TokenObserver`, `storeOrUpdateTokenHolders()`), never by the database itself.
  A direct DB write, a future bulk-delete query, or a bug anywhere in the PHP path can leave orphaned
  `token_holders`/`dim_table` rows with no constraint to prevent it.
- **No index on any filterable or lookup column besides the two unique columns.** The API's own
  documented filters (`docs/api-contract.md`) support `from_address`/`to_address` lookups on
  `transactions` and address/holder lookups on `token_holders` — none of these columns are indexed.
  Fine at today's data volume; a real cost once either table grows past a trivial size.
- **`transaction_type` is computed, not stored** — `TransactionTypes::forRecipient()` derives
  `Transfer` vs `Contract creation` from whether `to_address` is empty, at read time, in
  `TransactionResource`/wherever the resource renders `type`. This is a deliberate, correct choice
  (matches the README's own documented fix of an earlier bug) and avoids a redundant, potentially
  inconsistent stored column.
- **No N+1 risk found in the read paths that exist.** `TokenRepository`/`TransactionRepository`'s
  list queries are flat `where()->orderBy()->paginate()` with no eager-loaded relations in play on the
  hot path; `DIM::token()` (`belongsTo`) is never actually invoked by any controller.
- **Backup/recovery**: `docs/runbook.md` documents a manual `pg_dump` into the gitignored `var/`
  directory — a real, written procedure, but entirely manual with no schedule, retention, or
  off-host copy. No `scripts/restore.sh` content was read this phase (file exists at
  `scripts/restore.sh`, out of this phase's discovery depth); its existence is noted for Phase 5/9 to
  verify end-to-end.

## Dual-mode / standalone readiness (Phase 9's concern, assessed now for the plan)

The blockchain and Telegram integrations already degrade cleanly when unconfigured (`isConfigured()`
gates, 503/no-op rather than a crash) — a real, working head start on the Phase 9 "dual-mode
independence" standard. What is missing: a single `MODE=standalone` switch, and a clean
liveness/readiness split. Today there are three overlapping health signals — Laravel's own `/up`
(framework default), `/api/v1/check/health` (hardcoded `"status": "ok"`, checks nothing), and
`/api/v1/check/db` (the only one that actually probes a dependency) — with no `/health/live` vs
`/health/ready` distinction as the program standard expects.

## Docker topology

7 Dockerfiles exist; only 6 are reachable from `docker-compose.yml`'s 8 services (`nginx`, `php`,
`composer`, `artisan`, `test`, `lint`, `pgsql`, `redis` — `composer.dockerfile` and `php.dockerfile`
each serve two services). `node.dockerfile` is unreachable dead code (Phase 2 finding, Phase 7's to
remove) — `nginx.dockerfile`'s own comment already documents this precisely. All 6 live Dockerfiles
are digest-pinned; `postgres.dockerfile` additionally layers `pg_cron`, `postgis`, and `timescaledb`
onto the base Postgres image, none of which this phase found a single usage of in any migration or
query — PostGIS/TimescaleDB/pg_cron are installed but this repo has no geospatial or time-series
table, and no `SELECT cron.schedule(...)` anywhere. `php.dockerfile`/`test.dockerfile`/
`composer.dockerfile` all install `pdo_mysql`/`mysqli` despite the project exclusively using
PostgreSQL (`docker-compose.yml` has no MySQL service; `DB_CONNECTION=pgsql` is the only configured
default) — unused extension weight in every PHP-based image. `redis` is a local build (not a registry
pull, per the repo's own `R91` convention) with a healthcheck, but — per Phase 2 — the `phpredis`
extension it would need is installed in none of the PHP images, and the actual configured client
default is `phpredis`, not the installed `predis/predis` package; both `nginx` and `php` still
`depends_on: redis: condition: service_healthy` even though Redis is not on the critical path at the
shipped default config (`CACHE_DRIVER=file`, `QUEUE_CONNECTION=sync`).

## Where DRY/KISS/YAGNI/SOLID/Clean/DDD/EDA currently stand

- **Clean/Hexagonal-flavored layering** (Controller/Service/Repository/interfaces) — already applied,
  proportionately, to both features. Nothing to add here; Phase 8 should document the existing
  rationale in an ADR rather than introduce new layers.
- **YAGNI violation worth naming**: `laravel/horizon` (queue dashboard) and `doctrine/dbal` are
  installed with zero wiring/usage (Phase 2 findings) — the opposite of "apply only where it earns its
  place." Candidates for removal once Phase 2's dependency-decision process (standing decision 3)
  covers their removal.
- **DRY**: two empty private `updateA(){}` methods (one per controller) exist purely to carry an
  `@OA\Put` annotation block that belongs on the real `update()` method — a workaround, not a pattern,
  and worth collapsing in Phase 7/8.
- **DDD/EDA**: not warranted. This is CRUD-plus-one-integration with one real domain rule (derive
  transaction kind from recipient presence) — no bounded-context complexity or genuine async
  event-driven need exists today. The Telegram notification is a direct side-effecting call inside the
  repository, not a published domain event — acceptable at this scale; EDA would be over-engineering
  here (YAGNI wins).
