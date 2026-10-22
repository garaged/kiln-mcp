# Milestone 2 requirements: audited post discovery

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M2-001 | Kiln MUST expose one read-only `kiln/list-posts` ability that accepts a closed, bounded filter object and returns at most 20 post metadata records. | schema and pagination unit tests; real-WordPress contract |
| REQ-M2-002 | Execution MUST require `edit_posts`, exclude private content and post bodies, and return only post ID, title, status, modification time, and permalink. | administrator/author/subscriber integration matrix and output allow-list tests |
| REQ-M2-003 | Every successful or denied invocation MUST create a locally retained, redacted audit receipt. Receipts MUST include no post title, content, URL, identity name, credential, or input value. | audit persistence contract and redaction tests |
| REQ-M2-004 | The WordPress test environment MUST prove bounded output, author-level allowed access, subscriber denial, and local audit receipt creation. | container smoke test and Linux CI |

## Non-goals

M2 does not return post content, private posts, users, comments, media, arbitrary queries, bulk operations, or any write capability. It does not create a remote audit sink, telemetry, custom transport, or relay.
