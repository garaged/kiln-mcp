# ADR 0006: use a dedicated official-adapter server for Kiln transport contracts

## Status

Accepted for M8.

## Context

The MCP Adapter default server can expose public abilities registered by unrelated WordPress components. Testing Kiln only through direct `wp_get_ability()` calls does not prove MCP framing, session behavior, authenticated HTTP, or client discovery compatibility. Kiln needs a narrow, reproducible protocol surface without implementing its own transport or relay.

## Decision

Kiln registers one official-adapter-managed `kiln-mcp` server that explicitly allow-lists Kiln capabilities and enables the adapter's HTTP and STDIO transports. Its transport permission callback requires an authenticated user with `edit_posts`. Each ability's existing permission callback remains decisive, so server admission does not grant access to administrator-only diagnostics, extensions, or lifecycle effects.

The M8 test fixture treats the adapter release and MCP revision as compatibility inputs. It sends protocol messages through STDIO and authenticated HTTP, tests negative cases, and applies disposable response/latency budgets. Candidate upgrades and rollback use the same fixture; Kiln stores no client credentials or compatibility state.

## Consequences

Kiln gains an auditable MCP-facing boundary that excludes unrelated abilities and subscribers while retaining WordPress-native user policy. It gains protocol evidence without coupling core code to an MCP client implementation. This depends on the official adapter's supported server-registration API; an incompatible future adapter release is rejected by the compatibility suite rather than silently accepted.