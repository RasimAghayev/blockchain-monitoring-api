# Improvement roadmap

Phase 3 (`auditor`), 2026-11-05. This is a sequencing plan, not an implementation — nothing in this
file is executed by Phase 3. Ordering follows the program's own phase barrier (R2): a step cannot be
scheduled before the phase that owns it opens, and later steps assume earlier ones closed.

## Sequence

### Now unblocked (Phase 4 — git hygiene)
1. Rename default branch `master` → `main`; in the same change, update
   `.github/workflows/ci.yml:8-10`'s `branches: [master, test]` trigger, or CI silently stops firing
   on push after the rename.
2. Triage the 2 stale dependabot branches (rebase onto current `master`'s Laravel 12 baseline, or
   close) and all 13 non-main branches per the standing branch-hygiene rule.
3. Decide where Renovate/Dependabot's governing config should live (none is currently committed
   despite 10 renovate + 2 dependabot branches existing on the remote) and commit it.
4. Gitleaks the full history as part of this phase's own secret audit (Phase 3's scan already ran and
   found only the 3 known-fake test-fixture hex strings; Phase 4 should still run its own pass per its
   own checklist, not rely on Phase 3's result as a substitute).

### Phase 5 — container baseline
5. Record the real (currently red) CI/test baseline in `docs/BASELINE.md` — do not let the README's
   stale green table stand as the baseline source of truth.
6. Decide whether `redis`'s `service_healthy` gate on `nginx`/`php` startup should be relaxed to
   `service_started`, or removed, given Redis is unused at default config (ties into item 9 below).

### Phase 6 — upgrade (latest stable, standing decision 1)
7. Bump the 5 Symfony packages carrying the 8 live advisories to their fixed minor versions — this
   alone should turn CI's audit gate green again.
8. Work through the 7 major-version-behind direct PHP packages and 4 major-version-behind npm
   packages from `projects/info/deps/blockchain-monitoring-api.md`, one at a time, each verified in
   Docker per standing decision 1.
9. Fix the Vite `App.css`/`App.js` vs. `app.css`/`app.js` casing bug as part of the npm/Vite dependency
   work in this same phase (it is a one-line config fix, not a design decision — no reason to defer
   it to Phase 7/9).
10. Replace or drop the abandoned `doctrine/annotations` package.
11. Bump PHP 8.4→8.5, Postgres 16→18 (2 majors — verify Laravel 12 + all extensions used are
    compatible before committing), refresh the 4 stale image digests, bump `actions/checkout` to
    `v7.0.1`.
12. Resolve the `predis/predis` vs. `phpredis` mismatch: pick one client, make sure its runtime
    dependency (PECL extension for `phpredis`, nothing extra for `predis`) is actually present in
    every Dockerfile that needs it.

### Phase 7 — dead code removal
13. Delete `node.dockerfile` (confirmed dead: unreferenced by compose, targets a `src/fe` that does
    not exist in git).
14. Delete `updateA()` no-op methods in both controllers once their `@OA\Put` annotations are moved
    onto the real `update()` methods (or onto a dedicated annotation-only file, whichever the
    OpenAPI tooling in use supports); wire `ErrorMethodNotAllowedResponse` into `bootstrap/app.php`'s
    exception renderer (resolving the Pint-tokenizer comment first) so real 405s stop returning a
    500-shaped body.
15. Remove unused `pdo_mysql`/`mysqli` PHP extensions and unused `pg_cron`/`postgis`/`timescaledb`
    Postgres extensions from the relevant Dockerfiles.
16. Decide `doctrine/dbal` and `laravel/horizon`'s fate (remove if Phase 9's security/perf pass
    confirms neither is needed; wire up if a real use is found — e.g. Horizon only makes sense once
    `QUEUE_CONNECTION` moves off `sync`, which is itself a Phase 9 decision, not assumed here).
17. Reconcile the two `.env.example` files — `src/be/.env.example` should mirror the root one, not the
    stock Laravel skeleton.

### Phase 8 — architecture (apply only where a real problem exists)
18. No DDD/EDA work is planned here (see `docs/TECHNICAL_DEBT.md`'s "not treated as debt" section) —
    this repo's size and single-process nature do not justify it. The existing Controller → Service →
    Repository layering is already a reasonable Clean/Hexagonal-flavored fit; Phase 8's job on this
    repo is to confirm that, write the ADR saying so, and stop — not to introduce new structure.

### Phase 9 — hardening (security + performance + dual-mode + observability)
19. Re-enable `auth:api`/`authorize()` across both routers and both controllers (P0-1) — the single
    highest-priority item in this entire roadmap.
20. Lock down `check/clear_cache` (auth at minimum; consider removing the live-request cache-churn
    pattern entirely in favor of a deploy-time step).
21. Gate `TransactionHelper`'s exception-message exposure behind `config('app.debug')`; add the
    missing `unique:tokens,address` validation rule to `TokenRequestTrait::getCommonRules()`.
22. Attach `SecurityHeaders` (or the `'csp'` group) to the live middleware stack/route group — the
    headers are already written, this is a wiring fix.
23. Add foreign keys (`token_holders.token_address`→`tokens.address`,
    `dim_table.token_address`→`tokens.address`) and indexes on the filterable columns identified in
    the audit; add a unique constraint on `dim_table.token_id`.
24. Fix the Telegram `parse_mode` omission.
25. Fix the latent `ApiFilter` `'json'` operator tuple-order bug before (not after) anything ever
    enables a `json` filter.
26. Decide Redis's actual role (cache driver? queue driver via Horizon?) and resolve the
    `service_healthy` startup-gate question from item 6 accordingly.

### Phase 10 — CI/CD
27. Confirm the full pipeline (build → install → audit → test → lint) is green end-to-end once Phase
    6 lands, using the same live `gh api` check this phase used, not just a local run.
28. Finalize the Renovate/Dependabot governing config decision from item 3.

### Phase 11 — tests
29. Rewrite `TokenCrudTest::test_it_rejects_a_duplicate_address` to assert `422` (validation error)
    once item 21's fix lands, instead of the current `500` (the bug it currently locks in).
30. Add regression coverage for the now-enabled auth (401/403 paths) and for the `'json'` filter fix
    once Phase 9 adds it.
31. `src/fe` has no test runner at all per Phase 2 — not applicable to this repo today since `src/fe`
    doesn't exist in git; revisit only if a real frontend is ever added.

### Phase 15 — documentation
32. Correct the README's CI/CD status table once it reflects reality again.
33. Document the `DIM` module (currently a one-line mention in `OtherInfo.md`).
34. Rewrite or delete the stale, misleading `COMMAND.md`.

## Not scheduled (explicitly out of scope for this repo, with reason)

- Phase 12 (merge), Phase 13 (shared-lib extraction), Phase 14 (rename), Phase 16 (platform) — see
  `n/a` calls and reasons in `projects/info/analysis/blockchain-monitoring-api.md`'s Phase 4-11(+)
  plan section.
