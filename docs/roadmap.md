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
| M7 | Governed read-only extension SDK and policy manifests | Merged |
| M8 | Client interoperability and transport hardening | Merged |

## Remaining capability plan

| Milestone | Larger capability bundle | Primary user value | Security exit gate |
|---|---|---|---|
| M9 | Production installation and operator onboarding | Verifiable self-hosted release, compatibility diagnostics, and safe operational setup | Production-only ZIP + checksum, bounded administrator diagnostics, Linux/ARM64 acceptance |

## Delivery rules

Each milestone remains one pull request and one independently releasable capability bundle. A milestone may contain multiple related tools only when they share one authorization model, effect class, threat boundary, and acceptance environment.

No milestone expands to generic REST forwarding, arbitrary database/filesystem access, shell/PHP execution, telemetry, a required relay, or unauthenticated production access.

## Current position

Kiln has completed secure discovery (M1–M3), controlled draft authoring (M4), approval-gated lifecycle effects (M5), bounded site-structure development (M6), and a governed read-only extension SDK (M7). M8 proves Kiln’s narrow capability surface through the official adapter over STDIO and authenticated HTTP. M9 now makes that boundary operational through a production-only artifact, bounded administrator diagnostics, and secure onboarding; custom transports, browser CORS bypasses, relays, credential storage, extension writes, broad site configuration, navigation mutation, and permanent deletion remain deliberately deferred.