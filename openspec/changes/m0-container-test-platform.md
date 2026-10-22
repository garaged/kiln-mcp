# Change: M0 container test platform

## Intent

Provide a reproducible, self-hosted, no-cost test environment that runs the real Kiln plugin in disposable WordPress containers on Rancher Desktop for macOS and Docker/Compose on Linux.

## Scope

- Compose orchestration, native multi-architecture official images, WordPress bootstrap, and smoke runner;
- Make targets, CI execution, platform preflight, and documentation;
- loopback-only host exposure and disposable data.

## Non-goals

- Kubernetes deployment, an MCP transport, a relay, browser UI tests, production credentials, or external SaaS.

## Security impact

The environment uses test-only credentials, no production mounts, no privileged containers, no Docker TCP listener, explicit image tags, loopback-only port publication, and delete-on-cleanup volumes.

## Acceptance

- local preflight detects an unavailable daemon/Compose v2 and rejects TCP Docker hosts;
- test runner activates Kiln MCP in WordPress and validates the REST index;
- same Make target runs in Linux GitHub Actions;
- manual evidence covers Rancher Desktop Apple Silicon and Linux before support is claimed.
