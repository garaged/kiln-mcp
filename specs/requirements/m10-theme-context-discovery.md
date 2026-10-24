# Milestone 10 requirements: bounded theme-context discovery

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M10-001 | Kiln MUST expose exactly four M10 read-only abilities: `kiln/get-active-theme-context`, `kiln/list-block-templates`, `kiln/list-block-template-parts`, and `kiln/list-active-theme-patterns`. All MUST require `edit_theme_options` at execution time and record a bounded local redacted receipt for allowed and denied outcomes. | registrar, authorization tests, audit tests, and adapter contract |
| REQ-M10-002 | Active-theme context MUST return only name, stylesheet identifier, version, parent-theme state, and block-theme support state. It MUST use a closed empty input schema and a closed output schema. | schema and unit-contract tests |
| REQ-M10-003 | Template and template-part discovery MUST use closed paged input schemas, reject unknown input keys, and return at most 20 allow-listed metadata records per page. Template records are limited to slug, title, origin, and customization state; template-part records add only area. | bounds, pagination, schema, and real-WordPress fixture tests |
| REQ-M10-004 | Pattern discovery MUST enumerate only patterns owned by the active theme and return at most 20 records per page, each limited to slug, title, and categories. | ownership filtering, bounds, and fixture tests |
| REQ-M10-005 | M10 MUST not expose markup, HTML, block content, CSS, PHP, `theme.json`, file paths, URLs, user data, options, credentials, diagnostics, plugin inventory, or global pattern inventory; it MUST perform no mutation. | allow-list review, negative tests, and container smoke assertions |
| REQ-M10-006 | Classic/unsupported theme states MUST fail closed with a stable bounded capability state or empty result and MUST not trigger broad fallback discovery. | unsupported-state and negative integration tests |
| REQ-M10-007 | The dedicated official-adapter server MUST allow-list all and only the M10 core abilities alongside the previously admitted core and governed extension abilities. | server membership test and MCP contract fixture |
| REQ-M10-008 | Linux CI and Rancher Desktop ARM64 acceptance MUST retain M0–M9 coverage and add M10 unit, integration, transport, and manual evidence. | CI, container smoke, manual guide, and ARM64 acceptance record |

## Non-goals

M10 does not create or modify themes, templates, template parts, patterns, menus, styles, global styles, files, options, or transport. It does not return source/markup/content, theme configuration files, paths, URLs, screenshots, all registered patterns, plugin information, identities, credentials, or diagnostics.