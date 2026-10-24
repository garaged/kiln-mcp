# M8 intent: client interoperability and hardening

## Outcome

A developer can prove that Kiln capabilities are reachable through the official WordPress MCP Adapter over local STDIO and authenticated HTTP, with a stable protocol fixture suite rather than a client-specific manual test. Kiln remains self-hosted, uses no relay, and owns neither a custom wire protocol nor user credentials.

## Transport boundary

Kiln exposes a dedicated adapter-managed server named `kiln-mcp`. It contains the explicit Kiln capability allow-list only, never every public WordPress ability. The server permits authenticated WordPress users with `edit_posts` at the transport boundary; each ability retains its existing exact permission callback and approval policy. This deliberately refuses subscribers before discovery while preserving author, editor, and administrator development workflows.

The server uses the installed official MCP Adapter's HTTP and STDIO transports. Kiln supplies no proxy, custom transport, outbound connection, account, telemetry, or token store. HTTP credentials are standard WordPress Application Passwords or an equivalent host-provided authenticated WordPress context; credentials are never accepted, generated, returned, or retained by Kiln.

## Compatibility contract

M8 treats the adapter version as an explicit compatibility input. The disposable suite pins the supported adapter release and exercises MCP initialization, tool discovery, ability discovery, schema inspection, authorized execution, authorization denial, malformed request rejection, and bounded responses over both transports. The suite records the supported MCP protocol revision(s) rather than assuming one client implementation.

A controlled adapter-version override runs the same suite for an upgrade candidate. A rollback to the pinned version must restore the compatibility baseline without changing Kiln options, approvals, or audit-record shape. The pinned baseline is M8 evidence; a newer upstream adapter release triggers a recorded candidate-and-rollback rehearsal before that release may be adopted.

## Hardening boundary

HTTP acceptance must prove that an unauthenticated request is refused, an authenticated subscriber is refused at the dedicated server boundary, and response headers do not grant permissive cross-origin access. STDIO acceptance must prove user-context authorization and JSON-RPC framing without emitting diagnostics on standard output.

Performance assertions cover the disposable environment only: bounded fixture payloads, a fixed number of sequential requests, response-size limits, and a generous elapsed-time ceiling. They detect accidental unbounded work without pretending to be a production benchmark.

## Exit decision

M8 exits when the protocol matrix is repeatable in Linux CI and Rancher Desktop ARM64, a version upgrade/rollback rehearsal passes, all M1–M7 contracts remain green, and documentation gives exact local STDIO and authenticated HTTP connection guidance without exposing secrets.