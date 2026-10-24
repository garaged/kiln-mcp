# M10 intent: bounded theme-context discovery

## Outcome

A WordPress developer using an MCP client can understand the active theme’s basic identity and its available block templates, template parts, and active-theme patterns before making a design recommendation. This is a preview-first development capability: it supplies only small, structured metadata and cannot alter the theme or the site.

## Capability contract

M10 provides four read-only abilities, all requiring `edit_theme_options`:

- `kiln/get-active-theme-context` returns the active theme’s name, stylesheet identifier, version, parent-theme state, and block-theme support state.
- `kiln/list-block-templates` returns a closed page of at most 20 template summaries: slug, title, origin, and customization state.
- `kiln/list-block-template-parts` returns a closed page of at most 20 template-part summaries: slug, title, origin, customization state, and area.
- `kiln/list-active-theme-patterns` returns a closed page of at most 20 active-theme pattern summaries: slug, title, and categories.

The implementation treats classic and unsupported theme states as valid read-only outcomes. It returns a bounded capability state or an empty page instead of attempting broad discovery or leaking runtime diagnostics.

## Hard boundary

This milestone never returns template or pattern markup, HTML, block content, CSS, PHP, `theme.json`, source paths, URLs, user data, options, credentials, diagnostic details, or a plugin/global pattern inventory. It never creates, updates, deletes, imports, activates, or configures a theme, template, template part, pattern, menu, global style, file, or site option.

Each outcome creates an existing-style, local, redacted receipt. Receipts contain no theme identity, input, output, path, content, user identity, URL, or error detail.

## Exit decision

M10 exits when the four abilities are individually authorized, schema-closed, bounded, transport-allow-listed, and proven through unit, real-WordPress container, Linux CI, and Rancher Desktop ARM64 evidence. Any future theme write requires a separate milestone with an approval-gated design.