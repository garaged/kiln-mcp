# M3 intent: single-post read with content redaction

## Outcome

A WordPress developer can retrieve the bounded, plain-text body of one post they are permitted to edit through the official adapter, without converting Kiln into a generic content export API.

## Capability contract

`kiln/read-post` accepts exactly one positive integer:

- `post_id`.

The caller must have WordPress `edit_post` permission for that specific post. The capability admits only standard posts with `publish` or `draft` status. Private, scheduled, trashed, password-protected, and custom-post-type content are excluded even for administrators.

The result contains only post ID, title, status, modification time, permalink, bounded plain-text content, a truncation flag, and a fixed notice that the content is untrusted data. It never returns HTML, shortcodes, author identity, passwords, comments, revisions, custom fields, media metadata, or arbitrary post properties.

## Content boundary

Raw WordPress content is transformed before it crosses the tool boundary:

1. WordPress shortcodes and markup are removed.
2. Whitespace is normalized.
3. Output is capped at 12,000 UTF-8 characters.
4. A boolean makes truncation explicit.

Text is not treated as instructions. Tool metadata carries a fixed untrusted-data notice; no model-facing authorization or routing decision may be derived from post content.

## Audit boundary

Allowed and denied calls append a bounded local receipt using the existing fixed receipt schema. The receipt identifies only the ability, outcome, caller capability class, count, timestamp, and schema version. It never includes post ID, title, text, URL, request input, identity, credential, or error details.

## Exit decision

M3 exits only after unit and disposable real-WordPress proof of per-post authorization, content transformation and bound, exclusion of non-admitted statuses, denial auditing, and exact output shape. It does not introduce search, bulk read, writes, or media access.
