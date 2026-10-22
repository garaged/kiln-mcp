# Change: M3 single-post read with content redaction

## Why

A list of post metadata is not sufficient for common WordPress development workflows. The next safe increment is a single, permission-checked post reader—not a generic content export endpoint.

## Scope

- one exact-ID, per-post-authorized read capability;
- deterministic markup/shortcode removal, UTF-8 content bound, and explicit truncation;
- fixed untrusted-data marker;
- extension of the existing local redacted audit receipt model;
- unit, real-WordPress, and regression contract coverage.

## Constraints

- no access based solely on publication state; WordPress per-post capability remains mandatory;
- no private, scheduled, trashed, password-protected, or custom-post-type content;
- no raw HTML, shortcodes, user identity, media, comments, revisions, or arbitrary post fields;
- no generic REST forwarding, custom transport, relay, or remote audit sink.
