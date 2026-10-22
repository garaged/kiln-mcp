# Milestone 0 requirements

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M0-001 | Kiln MUST default to exposing no tools until an explicit policy allow-list is introduced. | ToolPolicy deny-by-default test |
| REQ-M0-002 | Domain policy code MUST be independent of WordPress runtime APIs, and the module graph MUST remain acyclic. | architecture check and static analysis |
| REQ-M0-003 | The project MUST publish a self-hosted/FLOSS security baseline, threat model, manual validation process, and executable quality entry point. | documentation review and make ci |
| REQ-M0-004 | M0 MUST expose no MCP site-management capability and make no required external network call. | isolated WordPress smoke evidence |
| REQ-M0-005 | The project MUST provide an automated, disposable real-WordPress test environment usable through Docker Compose on macOS Rancher Desktop and Linux. | container preflight, container test, Linux CI, and retained Apple Silicon/Linux manual evidence |

## Non-goals

M0 is not a usable site-management release. It establishes the constraints under which the first capability may be implemented.
