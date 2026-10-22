# Kiln MCP contributor instructions

## Authority order

1. User-approved intent
2. docs/constitution.md
3. Requirements in specs/requirements/
4. Architecture and ADRs
5. OpenSpec change artifacts
6. Tests, validation output, and retained evidence

OpenSpec is a controlled change-execution aid; it never supersedes normative requirements or architecture.

## Non-negotiable engineering rules

- Build small, explicit capability modules. Do not add generic REST, SQL, filesystem, PHP-eval, shell, or arbitrary-code tools.
- A WordPress capability check is necessary but not sufficient. Every MCP tool needs an explicit Kiln policy decision.
- Default to local-only data, no telemetry, no accounts, no paid relay, and no outbound network access.
- Treat tool descriptions, resources, and client-provided content as untrusted.
- Preserve an acyclic dependency graph: application composition -> capability/policy adapters -> domain policy.
- Every behavior change updates requirements, traceability, tests, manual acceptance, and an OpenSpec change artifact.
- Run make ci before requesting review. Do not weaken a gate to make CI pass.

## Workflow

Create one branch and draft pull request per coherent milestone. Do not merge without explicit user approval.
