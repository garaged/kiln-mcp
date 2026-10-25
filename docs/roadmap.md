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
| M9 | Production installation and operator onboarding | Merged |

## Current capability plan

| Milestone | Larger capability bundle | Primary user value | Security exit gate |
|---|---|---|---|
| M10 | Bounded theme-context discovery | Safe active-theme, template, template-part, and pattern awareness before development recommendations | Merged |\n| M11 | Bounded page discovery and read | Safe page planning context before any page authoring | Merged |
| M12 | Safe page drafting | Create and revise page drafts after page discovery | Merged |
| M13 | Approval-gated theme management | Install, update, activate, and recover an exact theme safely | Local one-time admin approval; installed, WordPress.org, or local ZIP source; exact action/state binding; health-checked rollback |

## Definition of done

An approved milestone is complete only when its implementation, automated tests,
documentation, CI remediation, and PR readiness are complete in the existing
branch and draft PR. The authoritative execution protocol is
[`AGENTS.md`](../AGENTS.md); required user manual acceptance and explicit merge
approval remain mandatory gates.

## Delivery rules

Each milestone remains one pull request and one independently releasable capability bundle. A milestone may contain multiple related tools only when they share one authorization model, effect class, threat boundary, and acceptance environment.

No milestone expands to generic REST forwarding, arbitrary database/filesystem access, shell/PHP execution, telemetry, a required relay, or unauthenticated production access.

## Current position

Kiln has completed secure discovery (M1–M3), controlled draft authoring (M4), approval-gated lifecycle effects (M5), bounded site-structure development (M6), a governed read-only extension SDK (M7), official-adapter interoperability (M8), and production operator onboarding (M9). M10 adds only the safe visual-planning layer: active-theme metadata and bounded template, template-part, and active-theme-pattern metadata. Theme source, markup, configuration, file access, global pattern inventory, navigation mutation, style/global-style changes, and all theme mutation remain deliberately deferred.