# M12 Rancher Desktop ARM64 acceptance

## Status

Passed — 2026-10-24.

## Environment record

- host: macOS Apple Silicon
- Docker context: `rancher-desktop`
- daemon architecture: `linux/arm64`
- Compose version: `5.3.1`
- fixture: WordPress `7.1.3-php8.2` with the repository-pinned MCP Adapter fixture

## Evidence

1. On the refreshed `feature/m12-page-drafting` branch, `make container-preflight`, `make container-test`, and `make container-down` passed on Rancher Desktop ARM64.
2. The suite created one active-user-owned standard page draft and revised it using its current opaque modification token.
3. It refused stale tokens, markup, unknown fields, and subscriber calls.
4. It confirmed the feature does not publish, schedule, or alter hierarchy, template, slug, author, metadata, taxonomy, media, password, or non-draft page state.
5. `make container-down` removed the disposable environment.

The automated test path publishes no host browser port. Optional browser inspection uses `make container-up` and may select a loopback port with `KILN_MCP_TEST_PORT=<free-port>`.
