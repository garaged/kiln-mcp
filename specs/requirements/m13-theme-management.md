# M13 requirements: approval-gated theme management

## REQ-M13-001 — bounded theme inventory
Kiln shall list at most 20 installed theme metadata records with allow-listed name, stylesheet, version, active state, parent indicator, and update availability. It shall not expose paths, source, screenshots, URLs, credentials, or plugin inventory.

## REQ-M13-002 — approved sources
Kiln shall admit only installed themes, a closed WordPress.org slug, or a locally administrator-uploaded ZIP identified by bounded metadata and an artifact digest. It shall refuse remote URLs, local paths, raw archive content, and MCP-supplied binary data.

## REQ-M13-003 — one-time local approval
Each installation, update, activation, or rollback shall require a current, unconsumed local `manage_options` approval bound to exact action, stylesheet, artifact/version identity, and pre-action state.

## REQ-M13-004 — bounded effects and rollback
Kiln shall execute one exact admitted operation using WordPress-native theme APIs, recheck state and capability immediately before effect, record only redacted local evidence, and provide health-checked rollback only to the immediately previous installed theme.

## REQ-M13-005 — validation
Tests shall prove source rejection, capability denial, approval expiry/replay refusal, exact binding, update/activation behavior, rollback, source redaction, and ARM64 Rancher Desktop acceptance.

## REQ-M13-006 — uploaded archive security scan
Before retaining, approving, or installing a locally administrator-uploaded theme ZIP, Kiln shall fail closed unless a bounded archive scan accepts it. The scan shall reject malformed or unsafe archive structure (including traversal, links, excessive entry/expansion limits, and unexpected executable payloads) and high-risk PHP execution or obfuscation patterns. It shall scan every PHP-bearing file, retain only a binary pass/fail verdict and fixed reason category, and expose neither source nor match detail. This lightweight static scan is a gate, not a malware-certification claim.
