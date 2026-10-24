# M9 Rancher Desktop ARM64 acceptance

- Status: pending manual verification
- Host: macOS Apple Silicon with Rancher Desktop
- Docker context:
- Daemon architecture:
- Compose:
- MCP Adapter:
- Kiln release ZIP:
- SHA-256:

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
