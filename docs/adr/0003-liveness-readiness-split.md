# ADR 0003: Liveness/readiness split

- Status: Accepted
- Date: 2026-11-25

## Context

`docs/ARCHITECTURE.md` noted three overlapping, unauthenticated health
signals (`/up`, `/api/v1/check/health`, `/api/v1/check/db`) with no
liveness/readiness distinction: `/up` and `/check/health` always return 200
regardless of any dependency, and `/check/db` conflates "is the process up"
with "is the database reachable" into a single check with no way to ask one
question without the other.

This matters for Phase 9's dual-mode/observability requirement
(`/health/live` + `/health/ready`, 12-factor-style) and for any real
orchestrator (Kubernetes, Docker Compose `depends_on: condition:
service_healthy`): liveness and readiness answer different questions and
should fail independently —

- **Liveness** false → the orchestrator should kill and restart the process
  (it is stuck/deadlocked).
- **Readiness** false → the orchestrator should stop routing traffic to it,
  but must **not** restart it (a dependency is down; restarting a healthy
  process fixes nothing and causes a restart loop).

## Decision

1. **Liveness** (`GET /api/v1/health/live`): process is up, can serve HTTP.
   No dependency checks — a DB or Redis outage must not make the
   orchestrator kill and restart an otherwise-healthy process.
2. **Readiness** (`GET /api/v1/health/ready`): process can actually serve
   traffic. Checks DB connectivity (`DB::connection()->getPdo()`) always, and
   additionally the Redis connection only when `CACHE_STORE=redis` or
   `QUEUE_CONNECTION=redis` — neither `.env.example` configures either driver
   as `redis` today (`docs/TECHNICAL_DEBT.md` item 13 tracks the two
   `.env.example` files not being fully reconciled with each other, separate
   from this), so checking a dependency nothing is actually configured to use
   would produce false negatives. Returns 503
   (not 500) when a checked dependency is unreachable, the correct semantic
   for "temporarily unavailable, try again / stop routing here" vs. a real
   application error.
3. The pre-existing `/up`, `/check/health`, `/check/db` routes are left
   exactly as they were (not deprecated, not removed) — they already have
   callers in local tooling and docs (e.g. the quickstart `curl` in
   `README.md`), and removing or changing their behavior is out of scope for
   this change. `/health/live` and `/health/ready` are additive.

## Explicitly out of scope (this ADR does not decide it)

An earlier draft of this ADR also proposed wiring up `laravel/horizon` (moving
`QUEUE_CONNECTION` to `redis` by default, adding a `horizon` service to
`docker-compose.yml`, and a scheduled command to run `transactions:fetch`
periodically) so that readiness's Redis check would have something real to
check. That is a materially bigger change — new service, new scaling
behavior, a default queue-driver change affecting every environment — than
"split one health check into two," and was never actually implemented
alongside this ADR despite an earlier draft claiming otherwise. Corrected
here rather than shipped as a doc that doesn't match the code (CLAUDE.md
honesty/testing-discipline: "docs match the real implementation"). The
decision of whether/how to wire Horizon remains open — tracked as-is in
`docs/TECHNICAL_DEBT.md` item 7, owned by a future Phase 7 or Phase 9 pass,
not by this ADR.

## Consequences

- Orchestrators (k8s, docker compose, swarm) can point restart-decision
  probes at `/health/live` and traffic-admission probes at `/health/ready`
  without the two questions being conflated, per the standard 12-factor
  pattern CLAUDE.md's Phase 9 checklist expects.
- `/health/ready`'s Redis branch is dead code under the current configuration
  (neither driver is set to `redis` in either `.env.example`) — written now so
  it activates automatically if a future change does move either driver to
  `redis`, rather than needing readiness logic revisited at the same time as
  that change.
