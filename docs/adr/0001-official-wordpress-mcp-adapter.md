# ADR 0001: Use the official WordPress MCP Adapter plugin

- Status: Accepted
- Date: 2026-10-22

## Context

Kiln needs MCP interoperability without duplicating protocol transport, discovery, or compatibility work. Its value is secure WordPress-development capabilities, not a competing MCP implementation.

The adapter is now a canonical WordPress plugin. Its maintainers discourage new plugins from bundling the Composer package because that can introduce dependency-version conflicts between site plugins.

## Decision

Kiln requires the official `mcp-adapter` WordPress plugin and uses the WordPress Abilities API for its capability registrations. Kiln does not bundle the adapter, implement an MCP transport, or create a competing endpoint. The official adapter default server is the initial protocol transport.

Kiln owns the policy-first capability layer: each capability remains responsible for its exact authorization, output allow-list, audit behavior when introduced, and privacy contract.

## Consequences

- Kiln tracks the adapter and WordPress compatibility explicitly.
- Kiln fails closed when the adapter or Abilities API is unavailable.
- Adapter discovery metadata is never treated as authorization; the WordPress capability check remains mandatory.
- A future optional relay is a separate deployable and cannot become a runtime requirement.
