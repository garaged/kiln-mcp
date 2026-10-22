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

## M1 test boundary

M1 combines unit tests for the exact data/schema allow-list with a real WordPress contract test. The container runs the official MCP Adapter plus Kiln, obtains the registered ability, verifies administrator execution and expected keys, then proves a subscriber receives a WordPress permission error. Protocol-stream interoperability is deliberately deferred until the first content capability adds a fixture-driven MCP client contract.

## Container baseline

The real-WordPress baseline is the container-test Make target. It builds the production plugin artifact, waits for a healthy database and WordPress instance, installs the pinned MCP Adapter, activates Kiln MCP in a fresh site, then executes an isolated smoke runner. The same target runs in Linux CI; manual Rancher Desktop Apple Silicon evidence is required before support is claimed.

Every confirmed AI- or reviewer-found defect adds a focused regression test before its fix is accepted.
