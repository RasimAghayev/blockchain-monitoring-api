# ADR 0001: Flat authorization (any authenticated user, no per-row ownership)

- Status: Accepted
- Date: 2026-11-25

## Context

`docs/REPOSITORY_AUDIT.md` (P0-1) found that authentication/authorization
infrastructure (Sanctum, JWT-auth, a `User` model, `$this->authorize(...)` calls)
was fully present but disabled at the route level — every CRUD endpoint for
`Token` and `Transaction` was reachable with zero credentials.

Re-enabling `auth:api` is straightforward. The harder question is what the
`authorize()` calls should actually check once the route is protected again,
because this domain has no concept of resource ownership:

- A `Token` is a watched on-chain address. It is not "owned" by the user who
  happened to create its row — it is shared reference data for the whole
  deployment (anyone with API access is expected to see and manage the same
  set of watched tokens).
- A `Transaction` is data fetched from an external blockchain API about a
  watched address. It has no relationship to any `User` at all.
- There is a single `User` model with no roles/permissions table, no `role`
  column, and nothing in the domain (API contract, migrations, existing
  tests) that implies more than one tier of access.

## Decision

`TokenPolicy` and `TransactionPolicy` authorize every ability (`viewAny`,
`createAny`, `view`, `update`, `delete`) for any authenticated `User`. The real
security boundary this repo needs today is binary: "presents a valid JWT for
the `api` guard" vs. "does not." That boundary is enforced by the `auth:api`
middleware on both routers; the policies exist so every controller action asks
the same explicit authorization question through the standard Laravel
mechanism (`$this->authorize(...)`), rather than relying on the middleware
alone — if a per-tier or per-owner model is ever introduced, there is already a
policy class per resource to extend, not routes to retrofit.

## Consequences

- Adding roles (e.g. a read-only "viewer" vs. a "maintainer" who can
  create/update/delete) later is a change to these two policy classes only —
  no controller or route changes required.
- This is intentionally **not** YAGNI-violating speculative RBAC: no role
  table, no permission enum, no abstraction beyond what Laravel's policy
  mechanism already requires to exist. KISS/YAGNI (CLAUDE.md standing decision
  7) still win — the policies are the minimum real implementation of "is
  authenticated," not a framework for a tiering system that doesn't exist yet.
- `check/clear_cache` is not part of this ADR — see
  `docs/adr/0002-remove-live-cache-clear-endpoint.md`: it is removed as an HTTP
  endpoint entirely rather than authorized, because running deploy-time
  maintenance commands from a live web request is itself the wrong shape,
  independent of who is allowed to trigger it.
