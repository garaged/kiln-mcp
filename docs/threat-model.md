# Threat model

## Assets

- WordPress content, configuration, users, and media
- WordPress identities and application passwords/tokens
- Capability policy, approval records, and audit log
- Site availability and integrity

## Primary threats and mandatory controls

| Threat | Controls |
|---|---|
| Stolen token / over-privileged identity | revocable credentials, least-privilege WordPress user, per-tool policy, rate limits, audit |
| Prompt injection from content/tool output | untrusted data treatment; fixed output notice; authorization never inferred from content; schemas and effect boundaries |
| DNS rebinding / cross-origin attack | HTTPS in production, strict Origin validation, authenticated transport, no permissive CORS |
| Destructive model action | writes disabled by default; effect classes; drafts before publish; explicit approval; trash/restore |
| Secret exfiltration | allow-listed fields, centralized redaction, no secret-returning tool, no generic REST/filesystem/SQL access |
| Diagnostic fingerprinting | authenticated adapter transport, `manage_options` gate, bounded allow-listed diagnostic output, no URLs/users/options/paths/database or plugin inventory |
| Content overexposure | `edit_posts` and per-post `edit_post` gates, metadata-only discovery, exact-ID read, private/status exclusion, output bounds, local redacted receipts |
| Active content and prompt injection | shortcodes and markup removed; output is plain text with a fixed untrusted-data notice; content cannot influence capability authorization or tool selection |
| Audit-log disclosure | local retention, allow-listed receipt fields, no identity name/ID, query/filter, content, URL, credential, or arbitrary error payload |
| Resource exhaustion | schema limits, pagination, rate limits, timeout budgets, bounded batch size, 12,000-character single-read cap |

## Explicit exclusions

Kiln will not provide arbitrary PHP execution, shell execution, SQL execution, unrestricted filesystem access, generic REST forwarding, or unconstrained search-and-replace.
