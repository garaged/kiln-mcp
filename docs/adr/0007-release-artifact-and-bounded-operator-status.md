# ADR 0007: assemble a local release artifact and expose bounded operator status

## Status

Accepted for M9.

## Context

Kiln uses an official MCP Adapter and deliberately has no hosted service. Operators need a practical way to install Kiln and diagnose dependency compatibility, but a rich diagnostic endpoint would risk revealing useful deployment or credential information.

## Decision

A local build command assembles a production-only ZIP and SHA-256 sidecar. It is deterministic in shape, excludes development material, and makes no network request beyond Composer's ordinary dependency resolution when a vendor tree is not already present.

Kiln adds a Tools-page status view protected by `manage_options`. It returns a fixed compatibility contract containing only Kiln version, PHP compatibility, WordPress Abilities API availability, and MCP Adapter presence/version/support. The page is registered before adapter capability registration, so an unavailable adapter is visible rather than silently indistinguishable. It performs no remediation or configuration.

## Consequences

Operators can verify and install a self-hosted artifact and diagnose the supported dependency baseline. They still configure HTTPS and WordPress identities themselves, retaining host control. The status view deliberately cannot be queried through MCP and does not become a public health endpoint.