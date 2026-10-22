# Milestone 1 requirements: read-only site diagnostics

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M1-001 | Kiln MUST declare the official `mcp-adapter` plugin as a runtime dependency and fail closed when the adapter or WordPress Abilities API is unavailable. Kiln MUST NOT bundle or implement its own MCP transport. | plugin-header and dependency-availability tests; real-WordPress activation test |
| REQ-M1-002 | Kiln MUST register exactly one `kiln/site-health-summary` ability through the WordPress Abilities API. It MUST have an empty object input schema, a strict documented output schema, and MCP discovery explicitly enabled. | registration contract test and schema fixture |
| REQ-M1-003 | Executing the ability MUST require `manage_options`, make no state change, and return only bounded development diagnostics: Kiln version, WordPress version, PHP major/minor, multisite state, active-theme stylesheet, and a fixed health status. | authorization, output, and no-mutation integration tests |
| REQ-M1-004 | The output MUST NOT include site title or URL, usernames, emails, roles, application passwords, options, plugin inventory, database details, filesystem paths, server headers, or arbitrary error text. | output allow-list unit tests and adversarial integration fixture |
| REQ-M1-005 | The disposable WordPress environment MUST install the pinned official MCP Adapter plugin before Kiln and prove that the Kiln ability is registered, executable by an administrator, and denied to a subscriber. | container smoke test and Linux CI |

## Non-goals

M1 does not introduce a custom MCP server, public unauthenticated access, a relay, audit persistence, configuration UI, content reads, or any write capability. The official adapter’s default server remains the sole protocol transport.
