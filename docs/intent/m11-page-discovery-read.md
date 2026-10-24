# M11 intent: bounded page discovery and read

## Outcome

A WordPress developer using an MCP client can safely identify and inspect the plain-text content of the standard pages they are already authorized to edit. This completes the read side of the deliberately narrow page workflow: plan, draft, approve, then publish.

## Capability contract

M11 provides two read-only abilities:

- `kiln/list-pages` requires `edit_pages` and accepts only status, limit, and page. It returns at most 20 standard published or draft page metadata records: ID, title, status, modification time, and permalink.
- `kiln/read-page` accepts one exact page ID and requires WordPress `edit_post` authorization for that page. It returns only the same fixed page metadata, a stable modification token, up to 12,000 characters of plain text, a truncation flag, and a fixed untrusted-data notice.

Both abilities exclude private, scheduled, trashed, password-protected, and non-page content. A missing, malformed, inaccessible, or unsupported target fails closed with a stable refusal. This milestone makes no write available.

## Hard boundary

M11 never returns raw HTML, block markup, shortcodes, comments, revisions, custom fields, page hierarchy, template assignment, author/user data, media metadata, URLs beyond the page permalink, options, credentials, diagnostics, filesystem data, or generic post properties. It never creates, updates, deletes, publishes, schedules, trashes, restores, assigns, or configures a page.

Every allowed or denied outcome creates a local, bounded, redacted receipt that contains no page identity, request input, content, URL, caller identity, credential, or error detail.

## Exit decision

M11 exits when both abilities are independently authorized, schema-closed, bounded, adapter-allow-listed, and proven with unit tests, real WordPress smoke coverage, Linux CI, and Rancher Desktop ARM64 evidence. M12 may introduce page drafts only as a separately designed write boundary.