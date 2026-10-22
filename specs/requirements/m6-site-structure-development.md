# Milestone 6 requirements: bounded site-structure development

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M6-001 | Registered post-type and taxonomy discovery MUST expose only bounded allow-listed public structure metadata to a manage_options identity. | closed-schema unit tests and real-WordPress authorization/output contract |
| REQ-M6-002 | Navigation-menu discovery MUST require edit_theme_options and return only bounded menu metadata, never menu items, locations, URLs, or arbitrary settings. | real-WordPress administrator/editor/subscriber matrix |
| REQ-M6-003 | Category/tag discovery MUST require manage_categories, use closed pagination input, and return bounded fixed metadata only. | unit request/record tests and container contract |
| REQ-M6-004 | Category/tag creation MUST accept only one bounded plain-text name and, for categories, one exact admitted parent ID; unknown fields, markup-like values, and invalid parents MUST refuse. | schema and negative integration tests |
| REQ-M6-005 | Exact duplicate normalized term creation MUST return the fixed existing record without a second mutation; no update, delete, assignment, custom-taxonomy, menu, or bulk operation MAY exist. | idempotency and forbidden-surface contract tests |
| REQ-M6-006 | All M6 outcomes MUST append bounded redacted local audit receipts, and Linux CI plus Rancher Desktop acceptance MUST retain M1–M5 coverage. | receipt contract, CI container smoke, and manual evidence |

## Non-goals

M6 does not create, update, delete, assign, merge, bulk-manage, or describe terms; change taxonomy/post-type registration; expose options or arbitrary structure fields; mutate menus, locations, or menu items; provide generic WordPress/REST access; add a relay; or alter M5's workflow approval boundary.
