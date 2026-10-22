# M0 intent: trustworthy starting point

## Outcome

A contributor can clone Kiln MCP, understand its security and architecture boundaries, run executable quality gates, and safely activate a non-invasive plugin skeleton.

## In scope

- governance, requirements, ADR, threat model, traceability;
- PHP plugin skeleton and pure policy nucleus;
- quality tooling and GitHub Actions;
- repeatable manual validation.

## Out of scope

- MCP tool exposure;
- user authentication flows;
- WordPress persistence;
- relay implementation;
- telemetry, paid plans, external accounts, or production site automation.

## Exit decision

M0 exits when required checks pass in CI and a clean WordPress activation/deactivation smoke test has recorded evidence. M1 then decides the first read-only diagnostic capability contract.
