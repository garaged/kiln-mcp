# Milestone 3 requirements: single-post read with content redaction

| ID | Requirement | Acceptance evidence |
|---|---|---|
| REQ-M3-001 | Kiln MUST expose exactly one `kiln/read-post` ability with a closed input containing only one positive integer `post_id`. | request-contract unit tests and real-WordPress contract |
| REQ-M3-002 | Execution MUST require WordPress `edit_post` permission for the requested post and admit only standard `publish` or `draft` posts. It MUST deny private, scheduled, trashed, password-protected, and custom-post-type content. | author/editor/subscriber and status matrix in container smoke tests |
| REQ-M3-003 | The result MUST contain only ID, title, status, modification time, permalink, bounded plain-text content, truncation state, and a fixed untrusted-data notice. It MUST remove markup and shortcodes and cap content at 12,000 UTF-8 characters. | content-transformer unit tests and exact output integration assertion |
| REQ-M3-004 | Each allowed or denied invocation MUST append an existing-schema local redacted audit receipt without post identity, post data, URL, request input, identity, credential, or error content. | receipt regression tests and container assertion |
| REQ-M3-005 | The portable WordPress environment MUST prove capability registration, per-post authorization, status exclusion, output redaction/bounds, denied receipt creation, and no regression in M1/M2. | Linux CI container smoke and Rancher Desktop evidence |

## Non-goals

M3 does not add content search, list expansion, multi-post or bulk read, writes, media, comments, revisions, users, arbitrary post types, raw HTML, shortcodes, remote auditing, telemetry, custom MCP transport, or relay infrastructure.
