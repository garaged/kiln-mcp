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

    make release-check
    make container-preflight
    make container-test
    make container-down

## Manual install verification

1. Run `make release` on the checked-out M9 branch.
2. Verify the ZIP with its SHA-256 sidecar using `shasum -a 256`.
3. In the disposable local site, install the official MCP Adapter and upload the verified Kiln ZIP.
4. As an administrator, open **Tools → Kiln MCP status**.
5. Confirm all four checks are ready and that the page contains no endpoint, credential, option, content, identity, filesystem-path, or plugin-inventory data.
6. Delete the local release ZIP after the test. Do not use production credentials or relax CORS.

## Result

Record the exact successful command output and the observed four readiness checks here. The disposable stack must be torn down with `make container-down`, removing containers, network, and data volumes.
