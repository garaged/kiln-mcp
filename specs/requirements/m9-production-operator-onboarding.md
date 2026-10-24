# Milestone 9 requirements: production installation and operator onboarding

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M9-001 | Kiln MUST provide a deterministic release builder that produces a `kiln-mcp` ZIP with a SHA-256 sidecar. The archive MUST contain only the plugin entry point, `src/`, production `vendor/`, GPL license, and operator readme; it MUST exclude development dependencies, tests, fixtures, CI, Git metadata, and local artifacts. | release builder, release archive inspection test, and CI |
| REQ-M9-002 | An administrator-only Tools page MUST report bounded Kiln, PHP, WordPress Abilities API, and official MCP Adapter compatibility states. It MUST be available when the adapter is absent or incompatible and MUST make no configuration changes. | status-policy unit contract and real-WordPress smoke assertion |
| REQ-M9-003 | The diagnostic page and its helpers MUST not expose credentials, application passwords, endpoint URLs, options, content, user data, filesystem paths, stack traces, or a plugin inventory. | allow-listed status contract and negative tests |
| REQ-M9-004 | Operator documentation MUST describe release verification, install/rollback, local STDIO, HTTPS authenticated HTTP, least-privilege identity, and revocation; it MUST prohibit disposable fixture use and CORS relaxation in production. | operator guide and manual acceptance |
| REQ-M9-005 | Linux CI and Rancher Desktop ARM64 acceptance MUST retain M1–M8 coverage and add release-artifact and administrator-status evidence. | CI quality/container smoke, manual guide, and M9 ARM64 record |

## Non-goals

M9 does not add MCP tools, a custom transport or server, a relay, browser CORS handling, OAuth, credential generation/storage, remote update checks, telemetry, automatic activation/update/rollback, or broader capability authority.