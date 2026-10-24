# M11 Rancher Desktop ARM64 acceptance

## Status

Pending manual acceptance.

## Required environment record

- date:
- host: macOS Apple Silicon
- Docker context:
- daemon architecture:
- Compose version:
- WordPress image and MCP Adapter version:

## Required evidence

1. A refreshed `feature/m11-page-discovery-read` branch passes `make container-preflight`, `make container-test`, and `make container-down`.
2. An administrator can list only bounded standard published/draft page metadata and read one admitted page as bounded plain text.
3. Markup, shortcodes, private/future/trashed/password-protected/non-page targets, hierarchy, template, author, comment, revision, custom-field, and media data are absent or refused.
4. A subscriber is denied both M11 abilities.
5. Before-and-after state confirms the calls do not change page or site state.
6. Cleanup removes the disposable environment.

Do not mark this record passed until the commands and observations above are performed on the local Rancher Desktop environment.