# M6 intent: bounded site-structure development

## Outcome

A WordPress developer can inspect the site structures that Kiln explicitly understands and seed one plain-text category or tag at a time, without acquiring a generic settings, taxonomy, menu-item, or navigation-write surface.

## Capability boundary

M6 is deliberately split by risk:

| Ability | Authority | Fixed boundary |
|---|---|---|
| kiln/list-supported-post-types | manage_options | bounded public post-type structure metadata only |
| kiln/list-supported-taxonomies | manage_options | bounded public taxonomy structure metadata only |
| kiln/list-navigation-menus | edit_theme_options | menu IDs, names, and item counts; no menu items or URLs |
| kiln/list-categories | manage_categories | bounded standard category metadata |
| kiln/list-tags | manage_categories | bounded standard tag metadata |
| kiln/create-category | manage_categories | one plain-text category name and optional exact parent ID |
| kiln/create-tag | manage_categories | one plain-text tag name |

Creation is idempotent: an existing exact normalized term returns fixed metadata without a second mutation. M6 does not update, delete, assign, merge, bulk-create, describe, slug, or expose arbitrary taxonomy fields. It does not create, edit, assign, or delete menus or menu items.

## Why menu writes remain excluded

Navigation changes can add or redirect public links and can be used for phishing, SEO manipulation, or site-wide workflow disruption. They need a separately designed approval boundary that binds a menu revision and item payload, rather than being smuggled into a general structure milestone.

## Output and data boundary

Structure output is allow-listed and bounded. It excludes plugin/theme origin, callback names, REST routes, capability maps, URLs, menu items, term descriptions, post assignments, user data, and arbitrary settings. All outcomes use the existing local redacted receipt model.

## Exit decision

M6 exits after unit contracts and disposable real-WordPress evidence prove authorization boundaries, strict schemas, bounded output, idempotent creation, no duplicate mutation, parent admission, unknown-field refusal, audit redaction, and M1–M5 regression coverage.
