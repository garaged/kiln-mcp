# M10 Rancher Desktop ARM64 acceptance

## Status

Passed on 2026-10-24.

## Environment record

- host: macOS Apple Silicon
- Docker context: `rancher-desktop`
- daemon architecture: `linux/arm64`
- Compose version: `5.3.1`
- WordPress image: `wordpress:7.1.3-php8.2-apache`
- MCP Adapter version: `0.7.1`

## Evidence

The refreshed `feature/m10-theme-context-discovery` branch passed:

    make container-preflight
    make container-test
    make container-down

The output confirmed the Rancher Desktop context and native Linux ARM64 daemon; healthy database, bootstrap, and WordPress services; `Container smoke checks passed.`; and complete removal of disposable containers, volumes, and network.

The M10 real-WordPress smoke contract exercised the deterministic active block-theme fixture and verified all of the following:

1. An administrator can invoke the four M10 abilities and receives only their documented fixed metadata fields.
2. Template, template-part, and pattern pages are bounded to 20 records and disclose no markup, content, source path, URL, CSS, PHP, theme configuration, credential, identity, or plugin data.
3. Unknown input and oversized pages are refused.
4. A subscriber is denied all four M10 abilities.
5. Before-and-after state snapshots confirm the calls preserve WordPress theme, template, global-style, navigation, pattern, and menu state.
6. `make container-down` removed the disposable environment.

No production WordPress site, credentials, or non-loopback Docker listener was used.
