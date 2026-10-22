# Architecture

## Pattern: policy-first modular monolith

Kiln MCP is a modular WordPress plugin. It is not a remote shell, generic API gateway, or database client.

WordPress/MCP Adapter transport -> application composition root -> capability modules and policy enforcement adapters -> domain policy and contracts.

### Components and allowed dependencies

| Component | Pattern | Owns | May depend on |
|---|---|---|---|
| src/Domain | Functional core/domain model | tool IDs, schemas, risk, policy decisions | PHP standard library only |
| src/Application | Composition root/use-case orchestration | module wiring, lifecycle | Domain and WordPress adapter interfaces |
| src/Infrastructure/WordPress | Adapter | WordPress APIs, adapter availability, ability registration | Application and Domain |
| src/Capability | Ports-and-adapters module | one explicit user-facing ability | PHP standard library only |
| testing/ | Disposable test adapter | real WordPress/MariaDB lifecycle and smoke evidence | Docker Compose only; never production runtime |

The dependency graph is acyclic. Only the WordPress infrastructure adapter uses WordPress APIs. Capability modules never share mutable persistence models.

## Protocol strategy

Kiln consumes the official WordPress Abilities API and the MCP Adapter plugin for MCP transport/discovery. Kiln remains responsible for its own capability registration, authorization, policy enforcement, audit receipts, redaction, and compatibility contracts.

M1 publishes one adapter-discoverable ability, `kiln/site-health-summary`. It is marked read-only and idempotent, checks `manage_options` at execution, and returns a fixed allow-listed schema. The adapter's discovery metadata is not authorization; WordPress capability checks remain mandatory.
