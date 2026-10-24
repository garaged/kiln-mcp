# M8 Rancher Desktop ARM64 acceptance

- Status: pending local acceptance
- Required host: macOS Apple Silicon with Rancher Desktop
- Required daemon architecture: linux/arm64

## Commands

    make container-preflight
    make container-test
    make container-down

## Evidence to record

Record the date, selected Docker context, daemon architecture, Compose version, adapter version, and the complete successful output. The result must include:

    Container smoke checks passed.

The M8 protocol matrix covers official-adapter STDIO and HTTP initialization, session-bound discovery and execution, dedicated-tool allow-listing, anonymous/subscriber/missing-session/malformed-request refusal, non-wildcard CORS headers, bounded response sizes, and the elapsed-time budget.

Do not use a production WordPress site or credentials. The disposable Application Password fixture is only permitted inside this loopback-only container environment. Verify teardown removes all containers, the network, and both data volumes.
