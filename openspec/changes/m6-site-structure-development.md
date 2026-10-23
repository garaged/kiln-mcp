# Change: M6 bounded site-structure development

## Why

WordPress development needs enough structural context to work safely with content types, taxonomies, and navigation configuration. Generic settings or taxonomy APIs would expose too much site internals and would create an unconstrained persistent-write path.

## Scope

- bounded read-only discovery for public registered post types and taxonomies;
- bounded navigation-menu metadata discovery;
- bounded category/tag discovery;
- exact, plain-text, one-at-a-time category and tag creation;
- idempotent term creation and redacted local audit evidence;
- unit and disposable real-WordPress contracts.

## Constraints

- no generic options, REST, database, filesystem, or shell forwarding;
- no term update, delete, assignment, descriptions, arbitrary slugs, bulk operations, or custom taxonomy mutation;
- no menu, menu-item, or menu-location mutation;
- no plugin/theme origin, callback, capability-map, URL, user, or arbitrary configuration disclosure;
- closed schemas, fixed outputs, resource-specific WordPress capability checks, and bounded result sets.
