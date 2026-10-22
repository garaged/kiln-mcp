# Testing strategy

## Test pyramid with security cross-cuts

| Layer | Purpose | Initial tool |
|---|---|---|
| Unit | Pure policy, schema, risk, redaction decisions | PHPUnit |
| Property/fuzz | malformed IDs, schemas, authorization combinations | PHPUnit data providers; mutation testing for policy code |
| WordPress integration | hooks, capabilities, persistence, REST/MCP Adapter | disposable WordPress test environment |
| Contract | MCP tool discovery and JSON Schema compatibility | fixture-driven MCP client suite |
| E2E | direct HTTP, authentication, origin rejection, approvals, audit receipts | containerized WordPress + MCP client |
| Security | secret scan, dependency audit, static analysis, adversarial authorization fixtures | CI |
| Release | install, upgrade, deactivate/reactivate, rollback, performance smoke | disposable environment + manual evidence |

A unit-test count is not a coverage target. Every requirement has a behavior-focused test at the lowest credible layer; boundary and security properties receive integration or contract coverage.

Every confirmed AI- or reviewer-found defect adds a focused regression test before its fix is accepted.
