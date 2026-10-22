# M4 intent: safe draft authoring

## Outcome

A permitted WordPress developer can create one standard-post draft and update one admitted draft through the official adapter, without acquiring publishing, deletion, arbitrary-field, or bulk-edit power.

## Capability contract

`kiln/create-post-draft` accepts exactly a bounded plain-text `title` and `content`. It creates a standard WordPress `post` owned by the active user with fixed `draft` status. It returns only fixed post metadata and a durable modification token.

`kiln/update-post-draft` accepts exactly one positive `post_id`, the current `expected_modified_token`, and bounded plain-text `title` and `content`. The target must be a standard, unprotected post already in `draft` status, and the caller must pass WordPress `edit_post` for that exact post.

Both contracts reject unknown fields, markup/PHP-like content, status/author/taxonomy/meta mutations, private or custom-post-type targets, publication, deletion, and bulk operations. The update rechecks the target, permission, and modification token immediately before the WordPress write and refuses a stale token.

## Modification token boundary

A token is derived from the post identity, WordPress modification state, and a local opaque revision value. Kiln refreshes that revision whenever a standard post is updated and after every allowed Kiln draft write. A client must read the post metadata again after a conflict; a stale write is never silently applied.

## Audit boundary

Allowed and denied create/update calls append the existing bounded redacted local receipt. A receipt includes no title, body, post ID, token, identity, input, URL, credential, or error data.

## Exit decision

M4 exits only after unit and disposable real-WordPress proof of creation, exact-target update, stale-token refusal, draft-only status preservation, author/editor/subscriber authorization, input/output bounds, redacted auditing, and M1–M3 regression coverage. It does not add publish, schedule, trash, restore, media, taxonomy, or options controls.
