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

## M7 extension boundary

M7 adds a governed extension adapter, not a plugin-execution sandbox. Installed extensions use a dedicated WordPress hook to populate a private staging registry with typed manifests. A provider's manifests are committed only as one valid, non-colliding batch; an exception or conflict discards that provider and dispatch continues. Core derives the logical identity `kiln-ext/{extension-id}/{ability-id}` and encodes the registered WordPress ability as `kiln-ext/{extension-id}--{ability-id}` because the Abilities API requires exactly one namespace slash. IDs refuse double hyphens, so the encoding is collision-free.

The WordPress adapter owns `manage_options`, MCP discovery, REST exclusion, readonly/idempotent metadata, callback wrapping, and generic redacted receipts. Extensions supply only label, description, closed schemas, and a local executor. This keeps the MCP-facing surface explicit while preserving the administrator trust boundary of installed WordPress code.

## M8 adapter transport boundary

M8 registers one dedicated `kiln-mcp` server through the official MCP Adapter. It allow-lists Kiln core and admitted extension ability names rather than inheriting every public ability from WordPress. The server’s transport admission requires an authenticated `edit_posts` user; individual ability callbacks continue to make the final per-tool authorization decision.

The adapter owns JSON-RPC framing, STDIO, HTTP sessions, and HTTP authentication. Kiln owns only server membership, the transport admission callback, policy contracts, and protocol conformance fixtures. It neither parses MCP requests nor stores client credentials, and it must not introduce a proxy, relay, CORS override, custom transport, or outbound service.