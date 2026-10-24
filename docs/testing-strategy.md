# Testing strategy

## Test pyramid with security cross-cuts

| Layer | Purpose | Initial tool |
|---|---|---|
| Unit | Pure policy, schema, risk, redaction decisions | PHPUnit |
| Property/fuzz | malformed IDs, schemas, authorization combinations | PHPUnit data providers; mutation testing for policy code |
| WordPress integration | hooks, capabilities, persistence, REST/MCP Adapter | disposable Docker Compose WordPress environment |
| Contract | MCP tool discovery and JSON Schema compatibility | fixture-driven MCP client suite |
| E2E | direct HTTP, authentication, origin rejection, approvals, audit receipts | containerized WordPress + MCP client |
| Security | secret scan, dependency audit, static analysis, adversarial authorization fixtures | CI |
| Release | install, upgrade, deactivate/reactivate, rollback, performance smoke | disposable environment + manual evidence |

A unit-test count is not a coverage target. Every requirement has a behavior-focused test at the lowest credible layer; boundary and security properties receive integration or contract coverage.

## Milestone boundaries

M1 combines unit tests for the exact data/schema allow-list with a real WordPress contract test. The container runs the official MCP Adapter plus Kiln, obtains the registered ability, verifies administrator execution and expected keys, then proves a subscriber receives a WordPress permission error.

M2 adds unit coverage for strict discovery input and receipt redaction. Its real-WordPress smoke runner creates published, private, and per-author draft fixtures; it proves the hard 20-item bound, the exact metadata-only response contract, author draft isolation, subscriber denial, and storage of allowed and denied redacted receipts.

M3 adds unit coverage for exact-ID read input and content redaction. Its real-WordPress contract proves per-post authorization, markup/shortcode removal, private exclusion, owned-draft access, output bounds, and redacted denied receipts.

M4 adds pure tests for closed draft-create/update requests and deterministic modification-token construction. Its real-WordPress contract proves creation under active-user ownership, exact draft update, stale-token conflict refusal, status preservation, author/editor/subscriber policy, markup/unknown-input refusal, output shape, and receipt redaction.

M5 adds pure approval-record tests and a real-WordPress lifecycle contract. It proves hash-only local approval storage, exact action/target/version binding, expiry semantics, single-use replay refusal, administrator-only local issuance wiring, current-token rechecks, publish/schedule/trash/restore result states, recovery from Trash, fixed metadata, redacted receipts, and subscriber denial.

M6 adds pure tests for closed page and taxonomy-seed inputs. Its real-WordPress contract proves fixed-field discovery bounds, category/tag idempotency, admitted category parents, markup/unknown-input/oversized-page refusal, subscriber denial, receipt attribution to the exact ability, and the deliberate absence of navigation mutation.

## Container baseline

The real-WordPress baseline is the container-test Make target. It builds the production plugin artifact, waits for a healthy database and WordPress instance, installs the pinned MCP Adapter, activates Kiln MCP in a fresh site, then executes an isolated smoke runner. The same target runs in Linux CI; manual Rancher Desktop Apple Silicon evidence is required before support is claimed. Protocol-stream interoperability remains a future fixture-driven contract once a dedicated MCP client suite is introduced.

Every confirmed AI- or reviewer-found defect adds a focused regression test before its fix is accepted.


## M9 release and operator boundary

M9 adds a pure status-contract test for supported and blocked dependencies, including negative assertions for credential, URL, and filesystem-path disclosure. A composition-root test proves the administrator status page remains registered when the official adapter is unavailable. The real-WordPress smoke suite renders the status page as an administrator and verifies its fixed ready contract without sensitive content. The release gate assembles a no-dev ZIP in an isolated temporary directory, validates its SHA-256 sidecar, verifies required production files, and rejects development-only files.


## M10 theme-context boundary

M10 adds pure tests for exact pagination and allow-listed active-theme, template, template-part, and pattern record shapes. The real-WordPress smoke contract proves all four abilities register on the dedicated server, administrator results contain only the fixed keys and never exceed 20 records, malformed/unknown input is refused, and a subscriber is denied. It does not inspect template or pattern content; test assertions reject those field names explicitly.