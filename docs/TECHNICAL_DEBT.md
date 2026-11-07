# Technical debt

Phase 3 (`auditor`), 2026-11-05. Combines every Phase 2 dependency/CI/Docker finding
(`projects/info/deps/blockchain-monitoring-api.md`) with this phase's own code-level findings
(`docs/REPOSITORY_AUDIT.md`). Each item is tagged with severity (P0-P3) and the phase expected to
resolve it, per the plan in `projects/info/analysis/blockchain-monitoring-api.md`.

## Real debt (needs a future phase to act)

| # | Item | Severity | Source | Resolving phase |
|---|---|---|---|---|
| 1 | No auth/authz on any CRUD route or on `check/clear_cache` — infrastructure present, disabled at the route/controller level | P0 | Phase 3 (new) | 9 |
| 2 | CI's audit gate is currently failing on `master` (live, verified via `gh api`) — test suite has not run since 2026-10-28ish | P0 | Phase 3 (new, built on Phase 2's CVE list) | 6 (fix), 10 (confirm pipeline green again) |
| 3 | Raw exception messages returned to callers unconditionally (`TransactionHelper.php:25-26`), with a confirmed trigger (missing `unique` rule on `tokenAddress`, test asserts the resulting 500) | P0 | Phase 3 (new) | 9 (debug-gating + validation fix), 11 (rewrite the test to expect 422) |
| 4 | 8 live Symfony advisories, transitive via `laravel/framework`, all same-minor-fixable | P1 | Phase 2 | 6 |
| 5 | 1 abandoned package (`doctrine/annotations`) | P2 | Phase 2 | 6 (replace/drop) |
| 6 | `doctrine/dbal` installed, zero usage found anywhere in the codebase | P1 | Phase 2 (confirmed again in Phase 3) | 7 (remove if Phase 6/9 confirm nothing needs it) |
| 7 | `laravel/horizon` installed, not configured (`config/horizon.php` absent), `QUEUE_CONNECTION=sync` by default so there is nothing to monitor | P1 | Phase 2 (confirmed again in Phase 3) | 7 or 9 (either wire it to a real queue or remove it — decision for the owning phase) |
| 8 | `predis/predis` installed but `REDIS_CLIENT` defaults to `phpredis`, and the `redis` PECL extension is installed in none of the 3 PHP-running Dockerfiles | P1 | Phase 2 (confirmed again in Phase 3) | 6/9 (pick one client consistently and install what it needs) |
| 9 | `nginx`/`php` compose services `depends_on: redis: condition: service_healthy` despite Redis being unused at default config (`CACHE_DRIVER=file`, `QUEUE_CONNECTION=sync`) | P1 | Phase 3 (new) | 5 or 9 |
| 10 | Both security-header middlewares (`SecurityHeaders`, `ContentSecurityPolicyMiddleware`) fully written, never attached to any route — zero security headers shipped today | P1 | Phase 3 (new) | 9 |
| 11 | Zero foreign keys, zero non-unique indexes across all 4 domain tables; referential integrity enforced only in PHP | P2 | Phase 3 (new) | 9 (hardener's perf/DB-integrity scope) |
| 12 | `DIM` shadow table: no unique constraint on `token_id` (race condition under concurrent `Token` writes), dead `order_price` cast, zero documentation beyond a one-line mention in `OtherInfo.md` | P2 | Phase 3 (new) | 9 (constraint), 15 (document it) |
| 13 | Two diverging `.env.example` files — root is real, `src/be/.env.example` is still the unmodified Laravel skeleton | P2 | Phase 3 (new) | 7 (cleaner) or 15 (documenter) — whichever phase owns env-file hygiene; flagged either way |
| 14 | `updateA()` dead methods in both controllers (annotation-hosting no-ops); `ErrorMethodNotAllowedResponse` written but unused, so real 405s today fall through to a 500-shaped body | P2 | Phase 3 (new) | 7 |
| 15 | Unused `pdo_mysql`/`mysqli` PHP extensions baked into 3 Dockerfiles; unused `pg_cron`/`postgis`/`timescaledb` Postgres extensions baked into `postgres.dockerfile` | P2 | Phase 2 (Dockerfile review) + Phase 3 (usage confirmation) | 5 or 6 |
| 16 | Telegram messages sent without `parse_mode`, so the Markdown syntax the message-builder emits renders as literal characters, not formatting | P2 | Phase 3 (new) | 9 |
| 17 | 7 of 17 direct PHP packages and 4 of 7 direct npm packages are a major version behind; PHP runtime 8.4→8.5 one minor behind; Postgres base image 2 majors behind (16→18); 4 images have stale digests under their pinned tag; `actions/checkout` pinned SHA is 3 majors behind `v7.0.1` | P1-P2 (mixed, see deps file for per-item severity) | Phase 2 | 6 |
| 18 | `node.dockerfile` is dead (unreferenced by compose, targets nonexistent `src/fe`) | P2 | Phase 2 | 7 |
| 19 | Vite entry-point casing bug (`App.css`/`App.js` referenced, actual files lowercase) + no build step anywhere in Docker/CI for `src/be`'s own Vite assets — the stock `/` welcome route would throw `ViteManifestNotFoundException` if ever hit outside testing | P1 | Phase 2 | 6 (fix casing) + 5/10 (add the missing build step) |
| 20 | `ApiFilter::buildQuery()`'s `'json'` operator branch has a reversed `[operator, column, value]` tuple vs. every other branch — latent (unreachable today), would break on first use | P3 | Phase 3 (new) | 9 or 11 (fix + add a test covering it before it's ever enabled) |
| 21 | `COMMAND.md` describes an unrelated generic Docker/Laravel template (wrong service names, wrong network name, wrong compose syntax) that doesn't match the real `docker-compose.yml` | P3 | Phase 3 (new) | 15 (rewrite or delete) |
| 22 | 2 dependabot branches are stale (pre-date the Laravel 11→12 bump already on `master`), not mergeable as-is; no `renovate.json`/`.github/dependabot.yml` committed despite 10 renovate + 2 dependabot branches existing on the remote | P2 | Phase 2 | 4 (git-steward triage) + 10 (decide/commit the governing bot config) |
| 23 | README's CI/CD status table shows all 5 gates green; live CI is currently red (see #2) — the doc is describing intended shape, not current truth | P2 | Phase 3 (new) | 15 (correct once #2 is actually fixed) |

## Debt that resolves itself once an earlier phase runs (listed so it isn't re-discovered and re-logged)

- Default branch still `master`, 13 stale bot/feature branches — Phase 4's job exactly; no separate action needed from this phase beyond flagging the CI workflow's `branches: [master, test]` trigger needs updating in the same PR that does the rename (`.github/workflows/ci.yml:8-10`), or the next push after the rename won't trigger CI at all.
- Every dependency-version gap in item 17 above is Phase 6's job by definition (standing decision 1); nothing here should be upgraded manually inside Phase 3's docs-only scope, and nothing was.

## Not treated as debt (considered and explicitly ruled out)

- **Vertical-slice duplication between `Tokens` and `Transactions`** (`*Router`/`*Service`/
  `*Repository`/`*Filters` pairs) — intentional, consistent template applied to two features. Does
  not meet standing decision 7's "real, present problem" bar for a DRY extraction; CLAUDE.md's
  KISS/YAGNI tiebreaker applies.
- **DDD aggregates / event-driven architecture** — repo has 4 tables and no cross-service message
  bus; introducing EDA here would be solving a problem that does not exist. Explicitly ruled out per
  standing decision 7, logged here so Phase 8 does not re-litigate it without new evidence.
- **MIT license / dependency license mix** — all permissive, no copyleft, no legal risk. Not debt.
- **3 gitleaks hits in test fixture data** (`0x1234...5678` test addresses) — confirmed false
  positive (fake hex string, not a credential), in both the working-tree and full-history scan. Not
  treated as a secret-handling debt item.
- **`TransactionHelper`'s misleading name** — cosmetic naming smell, folded into P3 in the audit, not
  escalated to a debt-tracker row of its own; fix opportunistically whenever that file is next
  touched for #3 above.
