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

## PHP and WordPress quality discipline

Treat PHP_CodeSniffer, PHPStan, and PHPUnit as implementation constraints, not
post-implementation cleanup.

- Complete one capability slice through a green `make ci` result before starting
  the next mutation, transport, or composition slice.
- Build in dependency order: pure capability contract and unit test; adapter;
  WordPress stubs/integration test; composition root; dedicated MCP allowlist.
- Do not leave unused production classes, staging stores, or partial registrars
  on a branch. Either integrate and test them in the same slice or keep the
  design in OpenSpec until the dependent path is ready.
- Every new or changed WordPress API call must update
  `tests/wordpress-stubs.php` with exact PHP signatures and iterable value
  types. Avoid duplicate declarations.
- Make PHPDoc precise enough for PHPStan: use array shapes for public records,
  preserve literal unions where the contract requires them, and narrow
  option-storage data before it reaches filesystem, hashing, or capability APIs.
- Never silence PHPStan or PHPCS broadly. A narrowly scoped suppression needs a
  documented WordPress API limitation and a focused regression test.
- When PHP quality fails, inspect the full report, fix all reported issues in
  that batch, and wait for a green rerun before beginning a new feature slice.
- Keep composition roots mechanically formatted; after adding a service, verify
  the entire declaration block rather than only the added lines.

## Workflow

Create one branch and draft pull request per coherent milestone. Do not merge without explicit user approval.


## Milestone execution

For an approved milestone, complete the entire scoped milestone in its existing
branch and draft PR without pausing after internal implementation slices.

Use bounded commits internally, but do not request direction or provide interim
handoffs for each slice. Continue through implementation, automated tests,
documentation, CI remediation, and PR readiness.

Stop only when:

- a material product, security, legal, licensing, or architecture decision is required;
- required manual acceptance must be performed by the user; or
- an external dependency prevents safe progress.

Do not merge without explicit user approval.
