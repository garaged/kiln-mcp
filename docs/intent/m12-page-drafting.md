# M12 intent: safe page drafting

## Outcome
Let an authorized MCP client create and revise standard WordPress page drafts safely after M11 has supplied bounded page context.

## Abilities
- `kiln/create-page-draft`: creates one active-user-owned, unprotected standard page in `draft` status from bounded plain-text title and content.
- `kiln/update-page-draft`: changes title and content of one admitted page draft only when its exact opaque modification token matches.

## Deliberate exclusions
No publish, schedule, trash, restore, delete, hierarchy/parent, menu, template, slug, author, comments, metadata, taxonomy, media, password, raw markup, shortcode, bulk, or cross-page operation. Page lifecycle effects require a separately approved milestone.

## Security contract
Creation requires `edit_pages`; an update requires exact `edit_post`. Inputs are closed and bounded. Writes preserve `draft` status and active-user ownership. The page is rechecked immediately after write, stale tokens fail closed, receipts remain local and redacted, and no request content or identity is retained.
