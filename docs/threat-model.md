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
| Prompt injection from content/tool output | untrusted data treatment; authorization never inferred from content; schemas and effect boundaries |
| DNS rebinding / cross-origin attack | HTTPS in production, strict Origin validation, authenticated transport, no permissive CORS |
| Destructive model action | writes disabled by default; effect classes; drafts before publish; explicit approval; trash/restore |
| Secret exfiltration | allow-listed fields, centralized redaction, no secret-returning tool, no generic REST/filesystem/SQL access |
| Resource exhaustion | schema limits, pagination, rate limits, timeout budgets, bounded batch size |

## Explicit exclusions

Kiln will not provide arbitrary PHP execution, shell execution, SQL execution, unrestricted filesystem access, generic REST forwarding, or unconstrained search-and-replace.
