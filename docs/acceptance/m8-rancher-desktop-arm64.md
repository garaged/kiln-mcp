# M8 Rancher Desktop ARM64 acceptance

- Status: passed
- Date: 2026-10-23
- Host: macOS Apple Silicon with Rancher Desktop
- Docker context: rancher-desktop
- Daemon architecture: linux/arm64
- Compose: 5.3.1

## Commands

    make container-preflight
    make container-test
    make container-down

## Result

Passed. `make container-preflight`, `make container-test`, and `make container-down` completed successfully. The database and WordPress services became healthy, bootstrap exited successfully, and the rebuilt smoke runner reported:

    Container smoke checks passed.

Teardown removed the disposable containers, network, and data volumes. The M8 protocol matrix covers official-adapter STDIO and HTTP initialization, session-bound discovery and execution, dedicated-tool allow-listing, anonymous/subscriber/missing-session/malformed-request refusal, non-wildcard CORS headers, bounded response sizes, and the elapsed-time budget.

Do not use a production WordPress site or credentials. The disposable Application Password fixture is only permitted inside this loopback-only container environment. Verify teardown removes all containers, the network, and both data volumes.
