# Milestone 4 requirements: safe draft authoring

## Intended bundle

M4 introduces two related capabilities: create one standard post draft and update one existing standard post draft. Both share the same draft-only effect boundary, authorization model, audit model, input constraints, and real-WordPress acceptance environment.

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M4-001 | Draft creation MUST accept only a bounded plain-text title and body, create a standard post with `draft` status owned by the active user, and return fixed metadata only. | schema/redaction unit tests and real-WordPress creation contract |
| REQ-M4-002 | Draft update MUST require exact post ID plus expected modification token, enforce `edit_post`, refuse conflicts, and never change post status. | concurrent-change and author/editor/subscriber matrix |
| REQ-M4-003 | Both capabilities MUST reject private/custom-post-type targets, arbitrary fields, markup/PHP-like input, raw option changes, publication, deletion, and bulk operations. | negative integration fixtures |
| REQ-M4-004 | Each allowed or denied mutation MUST create a bounded local redacted audit receipt, and successful mutations MUST return a durable modified token. | audit and idempotency contract tests |
| REQ-M4-005 | The test environment MUST prove create, update, conflict refusal, unauthorized denial, output bounds, and no M1–M3 regression on Linux and Rancher Desktop. | CI container smoke and manual evidence |

## Non-goals

M4 does not publish, schedule, delete, trash, restore, manage media/comments/users/options, accept arbitrary HTML or PHP, provide bulk editing, or add a relay.
