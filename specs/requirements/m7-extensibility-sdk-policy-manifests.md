# Milestone 7 requirements: extensibility SDK and policy manifests

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M7-001 | Core MUST admit extensions only through a typed, versioned manifest with normalized extension and ability IDs, a maximum of ten abilities, and fixed kiln-ext/{extension-id}/{ability-id} names. | manifest unit contracts and real-WordPress registration fixture |
| REQ-M7-002 | Every M7 ability MUST declare closed object input and output schemas and be read-only; manifests with unknown, malformed, open, duplicate, incompatible, or colliding declarations MUST be refused atomically. | validator property/negative tests and collision fixture |
| REQ-M7-003 | Core MUST apply its own fixed manage_options policy to every M7 extension ability. An extension MUST NOT supply a permission callback, public flag, transport, or write effect. | policy unit tests and administrator/subscriber container matrix |
| REQ-M7-004 | A malformed, duplicate, incompatible, or throwing extension MUST register no partial ability and MUST NOT stop core abilities or separately valid extension manifests from loading. | isolation unit tests and disposable WordPress fixture |
| REQ-M7-005 | Registered extension outcomes MUST append the existing bounded redacted local receipt; extension input, output, identity, callback, filesystem path, and exception details MUST NOT be retained. | receipt contract and container assertion |
| REQ-M7-006 | Linux CI and Rancher Desktop acceptance MUST retain M1–M6 coverage and add extension conformance coverage. | CI container smoke and manual evidence |

## Non-goals

M7 does not make extension installation safe automatically; installed PHP code remains an administrator trust decision. It does not admit extension writes, arbitrary callbacks from MCP input, custom permission logic, generic WordPress/REST/options/SQL/filesystem/shell access, extension marketplace or remote discovery, telemetry, transport changes, relays, or a user-visible extension administration page.