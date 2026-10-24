# M10 Rancher Desktop ARM64 acceptance

## Status

Pending manual acceptance.

## Required environment record

- date:
- host: macOS Apple Silicon
- Docker context:
- daemon architecture:
- Compose version:
- WordPress image and MCP Adapter version:

## Required evidence

1. `make container-preflight` reports the intended Rancher Desktop context and native Linux ARM64 daemon.
2. `make container-test` passes with M0–M10 smoke coverage.
3. An administrator can invoke the four M10 abilities and receives only their documented metadata fields.
4. Template, template-part, and pattern lists contain no more than 20 records and disclose no markup, content, source path, URL, CSS, PHP, theme configuration, credential, or identity data.
5. A subscriber is denied all four M10 abilities.
6. No invocation changes the active theme, template, pattern, menu, style, global style, file, or option.
7. `make container-down` removes the disposable test environment.

Do not mark this record passed until the commands and observations above are performed on the local Rancher Desktop environment.