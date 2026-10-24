# M12 Rancher Desktop ARM64 acceptance

## Status

Pending manual acceptance.

## Required evidence

1. A refreshed `feature/m12-page-drafting` branch passes `make container-preflight`, `make container-test`, and `make container-down`.
2. An authorized user can create one page draft and update it with its returned modification token.
3. Stale tokens, malformed or markup input, unknown fields, and subscriber calls are refused.
4. The ability cannot publish, schedule, alter hierarchy/template/slug/author, or change any non-draft page.
5. Cleanup removes the disposable environment.
