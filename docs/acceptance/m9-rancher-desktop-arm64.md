# M9 Rancher Desktop ARM64 acceptance

- Status: passed
- Date: 2026-10-23
- Host: macOS Apple Silicon with Rancher Desktop
- Docker context: rancher-desktop
- Daemon architecture: linux/arm64
- Compose: 5.3.1
- MCP Adapter: 0.7.1
- Kiln release ZIP: kiln-mcp-0.1.0-dev.zip, built locally with `make release-container`
- SHA-256: verified against the generated sidecar locally; value intentionally not retained in this record

## Verified outcome

- The containerized release gate passed without host Composer or a host `zip` utility.
- The verified ZIP uploaded through wp-admin, replaced the disposable fixture copy, and activated successfully.
- The administrator-facing Kiln MCP status view was operational after activation.
- `make container-test` passed on the same Rancher Desktop environment; it includes the non-root WordPress uploads write probe.
- The disposable stack was handled only through its loopback-bound test environment.
## Commands

    make release-container-check
    make container-preflight
    make container-test
    make container-down

## Manual install verification

1. Run `make release-container` on the checked-out M9 branch. This is the accepted Rancher Desktop path and does not require host Composer or a host `zip` utility.
2. Verify the ZIP with its SHA-256 sidecar using `shasum -a 256`.
3. Start a freshly bootstrapped disposable site with `make container-up` (run `make container-down` first if it was already running so volumes are recreated), upload the verified Kiln ZIP through `http://127.0.0.1:8080/wp-admin`, and activate it. The official MCP Adapter is already present in this disposable fixture. Kiln is also preinstalled from the checked-out source for smoke coverage, so use WordPress’s standard **Replace current with uploaded** action if shown.
4. As an administrator, open **Tools → Kiln MCP status**.
5. Confirm all four checks are ready and that the page contains no endpoint, credential, option, content, identity, filesystem-path, or plugin-inventory data.
6. Run `make container-down`, then delete the local release ZIP. Do not use production credentials or relax CORS.

## Result

Record the exact successful command output and the observed four readiness checks here. The disposable stack must be torn down with `make container-down`, removing containers, network, and data volumes.
