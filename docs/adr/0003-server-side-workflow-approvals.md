# ADR 0003: use server-side one-time approvals for workflow effects

## Status

Accepted for M5.

## Context

Publishing, scheduling, trashing, and restoring content are materially different from creating a draft. A client-side confirmation flag or a token minted by an MCP ability provides no meaningful independent approval boundary: an autonomous client can request it and immediately replay it.

## Decision

Kiln will expose a local WordPress administrator workflow approval screen. It creates a short-lived opaque value bound to exactly one action, post ID, and current modification token. Only a hash, expiry, action, target, version binding, and consumed status are stored. Workflow abilities require and consume that value before performing their effect.

The dashboard is local WordPress functionality, not an MCP tool. Approvals are never listed through MCP, exported, telemetered, logged, or included in redacted audit receipts.

## Consequences

Lifecycle work has an intentional operator step and will be slower than draft authoring. In return, a compromised or over-eager MCP client cannot produce an approval on its own, replay an old approval, switch targets/actions, or silently bypass version checks. Permanent deletion remains outside M5.
