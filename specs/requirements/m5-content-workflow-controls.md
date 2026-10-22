# Milestone 5 requirements: controlled content workflow

## Intended bundle

M5 introduces exact-target publish, schedule, trash, and restore abilities plus a local administrator-only approval issuer. They share the same lifecycle effect boundary, approval model, target/version binding, audit contract, and real-WordPress acceptance environment.

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M5-001 | Each lifecycle ability MUST accept only one positive post ID, the current modification token, and a one-time approval token; scheduling MAY additionally accept one future UTC timestamp. | closed-schema unit tests and real-WordPress contract |
| REQ-M5-002 | A local `manage_options` WordPress screen MUST issue an opaque approval bound to exactly one action, post ID, and modification token, store only a hash/expiry/consumed state, and never expose it through MCP. | dashboard issuance contract and storage/redaction test |
| REQ-M5-003 | Publish and schedule MUST admit only unprotected standard drafts; trash only unprotected standard drafts/published posts; restore only unprotected standard trashed posts. Every action MUST enforce `edit_post` on the exact target. | author/editor/subscriber and status/type negative matrix |
| REQ-M5-004 | An approval MUST expire, be action/target/version bound, and be consumed before its effect. Replay, stale target/version, mismatched action/target, invalid input, or failed admission MUST refuse without a post mutation. | token and idempotency/replay integration tests |
| REQ-M5-005 | Successful effects MUST return only fixed metadata plus a fresh modification token and append bounded redacted local audit evidence. Trash/restore MUST demonstrate recovery; permanent deletion is forbidden. | output, receipt, and recovery contract tests |
| REQ-M5-006 | CI and Rancher/Desktop acceptance MUST prove all M5 paths alongside M1–M4 regression coverage. | Linux CI container smoke and manual evidence |

## Non-goals

M5 does not permanently delete content, change arbitrary statuses, modify content/author/taxonomy/meta/options/media/comments/users, provide bulk operations, accept an approval issued through MCP, add a relay, or expose generic WordPress/REST access.
