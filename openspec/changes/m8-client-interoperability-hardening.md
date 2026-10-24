# Change: M8 client interoperability and hardening

## Why

Kiln must prove its policy-controlled WordPress abilities work through the official MCP protocol boundary, not only through direct WordPress ability invocation. The proof must be reproducible across local STDIO and authenticated HTTP without making a hosted relay part of the product.

## Scope

- a dedicated adapter-managed Kiln server with an explicit capability allow-list;
- explicit authenticated transport admission and preservation of per-ability authorization;
- fixture-driven STDIO and HTTP JSON-RPC protocol coverage;
- origin/authentication negative tests, response and elapsed-time budgets;
- adapter-version candidate and rollback compatibility support, with recorded evidence required before adopting a newer adapter release;
- CI, Rancher Desktop acceptance, upgrade guidance, and traceability updates.

## Constraints

- Kiln delegates MCP framing and transports to the installed official adapter;
- no custom HTTP proxy, relay, account, credential storage, OAuth provider, telemetry, or outbound network service;
- no unauthenticated public access, permissive wildcard CORS policy, client-specific capability behavior, or expansion of any tool's authority;
- compatibility evidence must use disposable credentials and environments only; raw secrets must never be logged or retained;
- a failed candidate upgrade must leave the pinned baseline usable and must not mutate Kiln policy data.