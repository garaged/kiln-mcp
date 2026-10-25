# M13 Rancher Desktop ARM64 acceptance

## Status

Pending manual acceptance.

## Required evidence

1. A refreshed `feature/m13-theme-management` branch passes `make container-preflight`, `make container-test`, and `make container-down`.
2. An administrator can approve and activate one installed fixture theme, with the prior active theme recorded.
3. Refused cases include missing/expired/replayed approvals, unknown source fields, remote URLs, paths, raw archive content, and subscriber calls.
4. A failed health check rolls back only to the immediate prior installed theme.
5. The test output and operator page reveal no archive content, source, path, URL, credential, or diagnostic stack detail.
