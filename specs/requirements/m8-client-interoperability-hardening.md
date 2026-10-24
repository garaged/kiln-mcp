# Milestone 8 requirements: client interoperability and hardening

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M8-001 | Kiln MUST register one dedicated official-adapter server containing only its explicit capability allow-list. The transport gate MUST require authenticated `edit_posts`; every ability MUST retain its own existing permission and approval checks. | server-registration unit contracts and real-WordPress authorization matrix |
| REQ-M8-002 | The disposable suite MUST exercise the pinned adapter over STDIO and authenticated HTTP for initialization, tool discovery, ability discovery, schema inspection, authorized execution, malformed-request refusal, and a bounded response contract. | protocol fixture runner and container smoke |
| REQ-M8-003 | Unauthenticated HTTP and authenticated subscriber requests MUST be refused. HTTP responses MUST NOT grant a wildcard cross-origin policy, and STDIO protocol output MUST remain valid JSON-RPC without diagnostic contamination. | negative transport fixtures and header assertions |
| REQ-M8-004 | The protocol fixture suite MUST enforce fixed request-count, response-size, and elapsed-time budgets appropriate to the disposable environment. | budget assertions and CI container evidence |
| REQ-M8-005 | The adapter release MUST be an explicit compatibility input. The suite MUST support a candidate-version run and a rollback to the pinned release while proving Kiln options, approvals, and audit receipt schemas remain compatible. When a release newer than the pin is available, that rehearsal MUST be recorded before Kiln adopts it. | upgrade/rollback command, compatibility fixture, and release-verification record |
| REQ-M8-006 | Linux CI and Rancher Desktop ARM64 acceptance MUST retain M1–M7 coverage and add the complete M8 protocol matrix. | CI container smoke, manual evidence, and M8 ARM64 acceptance record |

## Non-goals

M8 does not add a custom MCP wire protocol, relay, hosted service, client SDK, browser CORS bypass, credential issuer/store, OAuth provider, generic transport customization, public unauthenticated server, telemetry, generic WordPress/REST/options/SQL/filesystem/shell access, or broader Kiln capability authority.