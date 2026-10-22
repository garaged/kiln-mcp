# Security policy

## Reporting a vulnerability

Please report suspected vulnerabilities privately to the repository owner through GitHub Security Advisories. Do not open a public issue containing an exploit, credentials, or affected-site details.

## Security invariants

- No mandatory relay, account, analytics, or telemetry.
- HTTPS is mandatory outside localhost development.
- HTTP MCP endpoints validate Origin and reject disallowed origins.
- Every tool is deny-by-default, explicitly allow-listed, capability-checked, schema-validated, rate-limited, and audited with secrets redacted.
- Publish, destructive, credential, plugin/theme, and configuration actions require separate policy and explicit approval mechanisms.
- No arbitrary shell, PHP execution, SQL execution, file write, generic REST proxy, or unrestricted search-replace capability.
- Secrets are never logged, returned, copied into prompts, or stored in source control.
- Dependencies are pinned by lockfile, reviewed, scanned, and released with an SBOM.
