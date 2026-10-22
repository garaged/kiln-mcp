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

## Remaining capability plan

| Milestone | Larger capability bundle | Primary user value | Security exit gate |
|---|---|---|---|
| M4 | Safe draft authoring | Create and update standard-post drafts with optimistic concurrency, bounded input, explicit target ownership, and local receipts | No publish/delete; per-post authorization; conflict refusal; full real-WordPress author/editor/subscriber matrix |
| M5 | Content workflow controls | Explicit review, scheduled publish intent, trash/restore, and reversible lifecycle actions | Effect classification, confirmation token/approval record, idempotency behavior, recovery proof |
| M6 | Site-structure development | Narrow tools for categories, tags, menus, and registered post-type discovery | Closed resource schemas; no arbitrary options; capability-specific authorization and audit |
| M7 | Extensibility SDK and policy manifests | A FLOSS developer extension contract for independently versioned Kiln capabilities | Manifest validation, dependency isolation, deny-by-default policy, extension conformance suite |
| M8 | Client interoperability and hardening | Fixture-driven MCP protocol suite, authenticated transport evidence, performance and upgrade compatibility | STDIO/HTTP compatibility matrix, origin/auth negative tests, load budgets, upgrade/rollback checks |

## Delivery rules

Each milestone remains one pull request and one independently releasable capability bundle. A milestone may contain multiple related tools only when they share one authorization model, effect class, threat boundary, and acceptance environment.

No milestone expands to generic REST forwarding, arbitrary database/filesystem access, shell/PHP execution, telemetry, a required relay, or unauthenticated production access.

## Current position

Kiln has completed secure discovery (M1–M3), controlled draft authoring (M4), and approval-gated lifecycle effects (M5). M6 now adds bounded structural discovery and safe taxonomy seeding; broad site configuration, navigation mutation, and permanent deletion remain deliberately deferred.
