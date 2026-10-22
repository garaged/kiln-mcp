# Change: M5 controlled content workflow

## Why

Draft authoring must not grant a model automatic authority to publish or remove content. M5 adds a narrow lifecycle path with a human-created, server-side approval boundary rather than relying on an MCP client to self-confirm.

## Scope

- four exact-target workflow abilities: publish, schedule, trash, restore;
- a local WordPress administrator approval screen;
- action-, target-, and version-bound one-time approval records;
- strict status admission, per-post authorization, idempotency/refusal semantics, and redacted audit evidence;
- unit and disposable real-WordPress contract coverage.

## Constraints

- approval issue is not an MCP capability and raw approval values are never stored, logged, audited, or returned through MCP;
- only standard, unprotected posts in the documented source state are admitted;
- no permanent deletion, arbitrary status changes, content edits, author/taxonomy/meta/options/media/comments/user changes, bulk actions, relay, or generic forwarding;
- every mutation consumes approval before the effect, then rechecks the target and returns fixed metadata only.
