# ADR 0004: separate site-structure discovery from navigation mutation

## Status

Accepted for M6.

## Context

Categories, tags, registered content types, taxonomies, and menus are useful development context. They are also an easy route to accidental site-wide changes or site fingerprinting when exposed through generic administrative APIs.

## Decision

M6 will provide fixed, bounded discovery shapes and idempotent creation of one standard category or tag. It will use manage_options for registered-structure discovery, edit_theme_options for navigation-menu metadata, and manage_categories for standard-term operations.

M6 will expose navigation-menu metadata only. Creating or changing menus, locations, menu items, and their URLs is deferred until a later approval-gated design binds the exact structural revision and change payload.

## Consequences

M6 is immediately useful for content planning and taxonomy seeding without becoming a generic site administration channel. Developers who need navigation mutations must continue using the WordPress dashboard until a dedicated, independently reviewed capability is designed.
