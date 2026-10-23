# Product roadmap

## Delivered foundation

| Milestone | Outcome | Status |
|---|---|---|
| M0 | Secure SDD/IDD foundation, quality gates, portable WordPress container environment | Merged |
| M1 | Administrator-only runtime diagnostics | Merged |
| M2 | Bounded post discovery and local redacted audit receipts | Merged |
| M3 | Per-post authorized, bounded plain-text reading | Merged |
| M4 | Safe draft creation and optimistic-concurrency draft updates | Merged |
| M5 | Approval-gated publish, schedule, trash, and restore for exact posts | Merged |
| M6 | Bounded site-structure discovery and idempotent category/tag seeding | Merged |

## Remaining capability plan

| Milestone | Larger capability bundle | Primary user value | Security exit gate |
|---|---|---|---|
| M7 | Extensibility SDK and policy manifests | A FLOSS, read-only v1 developer extension contract for independently versioned Kiln capabilities | Atomic manifest validation, dependency isolation, fixed administrator policy, extension conformance suite |
| M8 | Client interoperability and hardening | Fixture-driven MCP protocol suite, authenticated transport evidence, performance and upgrade compatibility | STDIO/HTTP compatibility matrix, origin/auth negative tests, load budgets, upgrade/rollback checks |

## Delivery rules

Each milestone remains one pull request and one independently releasable capability bundle. A milestone may contain multiple related tools only when they share one authorization model, effect class, threat boundary, and acceptance environment.

No milestone expands to generic REST forwarding, arbitrary database/filesystem access, shell/PHP execution, telemetry, a required relay, or unauthenticated production access.

## Current position

Kiln has completed secure discovery (M1–M3), controlled draft authoring (M4), approval-gated lifecycle effects (M5), and bounded site-structure development (M6). M7 now establishes a governed read-only extension SDK; extension writes, custom authorization, broad site configuration, navigation mutation, and permanent deletion remain deliberately deferred.
