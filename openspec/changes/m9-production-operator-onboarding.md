# Change: M9 production installation and operator onboarding

## Why

A secure development capability is not operationally useful unless a site operator can install, verify, and diagnose it without relying on a paid relay or inspecting source code. Kiln needs a release and onboarding surface that remains as narrow as its MCP boundary.

## Scope

- deterministic production release ZIP plus SHA-256 sidecar;
- administrator-only, read-only compatibility diagnostics;
- install, verification, rollback, STDIO, and HTTPS HTTP operator guidance;
- unit, archive, real-WordPress, CI, and Rancher Desktop acceptance evidence.

## Constraints

- the status page is an allow-listed read-only view and cannot change configuration;
- output must contain no credential, endpoint, option, content, identity, path, or stack-trace data;
- release assembly uses only local dependencies and never uploads or phones home;
- production guidance preserves the official adapter transport boundary, HTTPS, least privilege, credential revocation, and no wildcard CORS;
- no automatic deployment, update, or rollback is introduced.