# ADR 0005: govern extension capabilities through a read-only manifest SDK

## Status

Accepted for M7.

## Context

WordPress developers need extensibility, but accepting a callback or generic configuration from an MCP client would turn Kiln into an unbounded execution and administrative channel. WordPress plugins are already executable local code; the MCP boundary must make their exposure explicit and reviewable.

## Decision

M7 defines a typed, versioned manifest SDK. An installed extension explicitly registers a manifest during a dedicated WordPress hook. Core validates the full manifest atomically before registering any ability.

Version one permits only read-only abilities, keeps their logical identities under kiln-ext/{extension-id}/{ability-id}, and encodes their WordPress ability names as kiln-ext/{extension-id}--{ability-id}; the platform accepts exactly one namespace slash. IDs forbid double hyphens so this encoding is collision-free. Core, not extensions, sets public MCP discovery, requires closed object input/output schemas, uses a fixed manage_options permission policy, and records redacted outcome receipts. Extensions may not provide custom permission callbacks or public/tool-transport flags.

## Consequences

Developers can ship independently versioned, discoverable capability modules without forking Kiln. The initial authority is intentionally narrow: write effects, custom authorization, richer policy composition, remote registries, and extension marketplace behavior require future milestones and separate threat modeling.