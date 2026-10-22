# Architecture

## Pattern: policy-first modular monolith

Kiln MCP is a modular WordPress plugin. It is not a remote shell, generic API gateway, or database client.

WordPress/MCP Adapter transport -> application composition root -> capability modules and policy enforcement adapters -> domain policy and contracts.

### Components and allowed dependencies

| Component | Pattern | Owns | May depend on |
|---|---|---|---|
| src/Domain | Functional core/domain model | tool IDs, schemas, risk, policy decisions | PHP standard library only |
| src/Application | Composition root/use-case orchestration | module wiring, lifecycle | Domain and WordPress adapter interfaces |
| future src/Infrastructure/WordPress | Adapter | WordPress APIs, persistence, audit sink | Application and Domain |
| future src/Capability | Ports-and-adapters module | one explicit user-facing ability | Application, Domain, WordPress interfaces |
| testing/ | Disposable test adapter | real WordPress/MariaDB lifecycle and smoke evidence | Docker Compose only; never production runtime |

The dependency graph is acyclic. Only infrastructure uses WordPress APIs. Capability modules never share mutable persistence models.

## Protocol strategy

Kiln consumes the official WordPress Abilities API and MCP Adapter for MCP transport/discovery. Kiln remains responsible for its own capability registration, authorization, policy enforcement, audit receipts, redaction, and compatibility contracts.

Milestone 0 intentionally publishes no abilities. Milestone 1 may publish read-only site diagnostics after its threat model and contract tests are accepted.
