# M11 Rancher Desktop ARM64 acceptance

## Status

Passed — 2026-10-24.

## Environment record

- host: macOS Apple Silicon
- Docker context: `rancher-desktop`
- daemon architecture: `linux/arm64`
- Compose version: `5.3.1`
- fixture: WordPress `7.1.3-php8.2` with the repository-pinned MCP Adapter fixture

## Evidence

1. On the refreshed `feature/m11-page-discovery-read` branch, `make container-preflight`, `make container-test`, and `make container-down` passed on Rancher Desktop ARM64. The successful test was rerun from a clean disposable environment after teardown of an earlier manual stack.
2. The container suite verified bounded standard published/draft page metadata and a bounded plain-text read of an admitted page.
3. It verified that markup, shortcodes, protected and unsupported targets, and out-of-scope page/site metadata are absent or refused.
4. It verified subscriber denial for both M11 abilities.
5. It verified before-and-after WordPress state preservation.
6. `make container-down` removed the disposable environment.

The automated test path publishes no host browser port. Optional browser inspection uses `make container-up` and may select a loopback port with `KILN_MCP_TEST_PORT=<free-port>`.
