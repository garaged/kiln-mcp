# Change: M7 extensibility SDK and policy manifests

## Why

Kiln needs a FLOSS path for WordPress developers to add narrowly scoped capabilities without forking core or turning its MCP surface into a generic administrative API.

## Scope

- typed, versioned extension and ability manifest contracts;
- atomic manifest validation and duplicate/collision refusal;
- a local WordPress extension registration hook;
- read-only, manage_options-gated extension abilities with closed schemas;
- redacted outcome receipts and extension conformance fixtures;
- unit and disposable real-WordPress acceptance coverage.

## Constraints

- M7 admits no persistent extension write ability;
- extensions cannot provide their own permission callback, arbitrary ability name, public flag, transport, account, relay, telemetry, or outbound core network behavior;
- no generic PHP, shell, SQL, filesystem, REST, options, or WordPress forwarding capability;
- malformed, duplicate, incompatible, or throwing extensions must not register partial abilities or block Kiln core;
- installed extension code remains a local administrator trust decision, documented separately from MCP client authority.