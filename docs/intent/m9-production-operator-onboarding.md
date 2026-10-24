# M9 intent: production installation and operator onboarding

## Outcome

A site operator can obtain a deterministic, self-contained Kiln release ZIP, install it beside the official MCP Adapter, and determine whether Kiln is safe to activate without disclosing credentials, endpoints, content, or site configuration. The operator receives exact local STDIO and HTTPS HTTP guidance, while Kiln continues to own no relay, OAuth flow, credential store, telemetry, or custom transport.

## Release contract

The release builder assembles only the WordPress plugin entry point, production PHP source, production Composer vendor tree, license, and operator-facing readme into a directory named `kiln-mcp`. It writes a SHA-256 sidecar for the ZIP and refuses an incomplete or polluted archive. Development tests, container fixtures, Git metadata, CI files, and Composer development dependencies are excluded.

## Operator contract

An administrator-only Tools page reports a bounded compatibility state: Kiln version, PHP runtime compatibility, WordPress Abilities API availability, and official MCP Adapter availability/version. It must be available even when the adapter is absent or incompatible, and it must never render credentials, endpoint URLs, WordPress options, users, content, filesystem paths, stack traces, or plugin inventory.

## Connection boundary

STDIO remains the preferred local development path through the official adapter. HTTP is for HTTPS deployments with a dedicated least-privileged WordPress user and revocable Application Password; operators must never expose the disposable container fixture or broaden CORS. The page reports readiness only—it does not configure transport, create credentials, or change any authorization policy.

## Exit decision

M9 exits when release assembly is reproducible and inspectable, compatibility diagnostics are fail-closed and administrator-only, Linux CI and Rancher Desktop ARM64 run the complete regression suite, and operator documentation gives safe install, verification, rollback, and connection guidance.