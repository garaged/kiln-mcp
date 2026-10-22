# ADR 0001: Use the official WordPress MCP Adapter

- Status: Accepted
- Date: 2026-10-22

## Context

Kiln needs MCP interoperability without duplicating protocol transport, discovery, or compatibility work. Its value is secure WordPress-development capabilities, not a competing MCP implementation.

## Decision

Use the WordPress Abilities API and official WordPress MCP Adapter as the protocol integration foundation. Kiln owns the policy-first capability layer and will use a custom MCP server configuration only where it needs explicit tool exposure.

## Consequences

- Kiln tracks adapter and WordPress compatibility explicitly.
- Kiln cannot assume adapter defaults are sufficient for authorization, audit, approval, or privacy.
- A future optional relay is a separate deployable and cannot become a runtime requirement.
