# Milestone 11 requirements: bounded page discovery and read

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M11-001 | Kiln MUST expose exactly two M11 read-only abilities: `kiln/list-pages` and `kiln/read-page`. Allowed and denied outcomes MUST create a bounded local redacted receipt. | registrar, audit tests, and adapter contract |
| REQ-M11-002 | `kiln/list-pages` MUST require `edit_pages` at execution time, accept only closed status/limit/page input, admit only standard published or draft pages, and return no more than 20 fixed metadata records per page. | authorization, bounds, schema, and fixture tests |
| REQ-M11-003 | `kiln/read-page` MUST require exact per-page `edit_post` authorization at execution time, accept only one positive page ID, and recheck authorization immediately before returning its result. | per-page authorization and denial tests |
| REQ-M11-004 | Page read output MUST contain only ID, title, status, modification time, modification token, permalink, bounded plain text, truncation state, and a fixed untrusted-data notice. It MUST remove markup and shortcodes and cap text at 12,000 characters. | unit redaction and real-WordPress content tests |
| REQ-M11-005 | M11 MUST refuse private, future, trashed, password-protected, missing, and non-page targets; it MUST not reveal page hierarchy, template, author, comments, revisions, custom fields, media metadata, raw markup, or arbitrary properties. | negative unit and integration tests |
| REQ-M11-006 | M11 MUST perform no page or site mutation and MUST not expose source files, options, credentials, identities, diagnostics, plugin inventory, generic REST, filesystem, database, shell, PHP, transport, relay, or telemetry access. | allow-list review, state-preservation, and negative smoke tests |
| REQ-M11-007 | The dedicated official-adapter server MUST allow-list exactly both M11 core abilities alongside previously admitted Kiln core and governed extension abilities. | server-membership test and MCP contract fixture |
| REQ-M11-008 | Linux CI and Rancher Desktop ARM64 acceptance MUST retain M0–M10 coverage and add M11 unit, integration, transport, and manual evidence. | CI, container smoke, manual guide, and ARM64 acceptance record |

## Non-goals

M11 does not create, update, publish, schedule, trash, restore, move, assign, configure, import, export, or delete pages. It does not expose raw content/markup, hierarchy, template assignment, source data, identities, credentials, diagnostics, files, or unrestricted site data.