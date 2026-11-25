# ADR 0002: Remove `GET /check/clear_cache` as a live HTTP endpoint

- Status: Accepted
- Date: 2026-11-25

## Context

`docs/REPOSITORY_AUDIT.md` (P1-1) found `CheckController::clearCache()`
reachable at `GET /api/v1/check/clear_cache` with no authentication beyond the
blanket rate limiter, running seven `Artisan::call()` maintenance commands
(`optimize:clear`, `config:cache`, `config:clear`, `clear-compiled`,
`cache:clear`, `route:cache`, `view:clear`) on every hit. `config:cache` and
`route:cache` are deploy-time operations in a normal Laravel app; this repo's
own routing is also unusual (`routes/api.php` scans the filesystem for
`Router/*.php` files at boot — see `docs/ARCHITECTURE.md`), so repeatedly
re-caching the route table from inside a web request is a self-inflicted
churn vector, not merely an auth gap.

Two options were available: put `auth:api` on the route (consistent with
ADR 0001), or remove the live endpoint and replace it with the deploy-time
primitive it should have been from the start. The roadmap written in Phase 3
(`docs/IMPROVEMENT_ROADMAP.md`, item 20) already flagged the second option as
worth considering instead of just gating the first.

## Decision

Remove the route and controller method entirely. The same operations are now
a single `php artisan optimize` / `make cache-clear` step (added to the
`Makefile`), run by whoever deploys — a human via `make`, or a future CI/CD
deploy job — never by an inbound HTTP request. `/check/db`, `/check/health`,
`/check/static`, `/check/ip` are unaffected; they are genuine read-only
diagnostics and stay (health/readiness details: see ADR 0003).

## Consequences

- One less unauthenticated attack surface; no policy/middleware decision
  needed for an endpoint that should not exist as an endpoint.
- Clearing caches now requires container/CLI access (`make cache-clear` ->
  `docker compose run --rm artisan optimize:clear` etc.), which is exactly
  the access level this operation always should have required.
- `docs/api-contract.md` and `README.md` are updated in the same change so
  neither documents a route that no longer exists.
