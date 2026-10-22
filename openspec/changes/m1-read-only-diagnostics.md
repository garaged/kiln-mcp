# Change: M1 read-only diagnostics

## Why

Kiln needs an end-to-end MCP capability that proves the policy-first architecture on a real WordPress site without exposing content or creating an irreversible action surface.

## Scope

- declare the official MCP Adapter plugin dependency;
- register `kiln/site-health-summary` with the WordPress Abilities API;
- enforce the administrator capability at execution;
- add pure-contract, WordPress integration, and container coverage;
- document connection and privacy boundaries.

## Constraints

- no bundled adapter, custom MCP transport, relay, persistence, or writes;
- no unauthenticated endpoint;
- bounded allow-listed output only;
- default server exposure is explicit and still subject to adapter and WordPress authorization.
