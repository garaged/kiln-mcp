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
| Unauthorized or overbroad authoring | fixed draft-only status, active-user ownership, exact-ID update, `edit_posts`/`edit_post` gates, closed schemas, no meta/taxonomy/options fields |
| Lost updates | opaque modification token, token recheck immediately before write, conflict refusal, durable refreshed token after every write |
| Stored active content | bounded plain-text title/body contract, markup/PHP-like input rejection, no media or arbitrary HTML |
| Unauthorized lifecycle effect or self-confirmation | local `manage_options` issuer only; no MCP approval tool; hash-only action/target/version-bound approval; ten-minute expiry; atomic one-time claim before effect; exact state and `edit_post` rechecks |
| Unintended permanent deletion | exact trash operation only; refuse when WordPress Trash is disabled; no delete ability or generic status interface |
| Unauthorized taxonomy or structural changes | seven fixed M6 abilities only; closed schemas; standard category/tag taxonomy allow-list; admitted category parents; idempotent seed only; no update/delete/assign/merge/bulk/custom taxonomy/meta/slugs/descriptions |
| Navigation phishing or sitewide workflow disruption | menu metadata discovery only; no menu, menu-item, or location mutation capability; any future navigation write requires a separately approved design |
| Extension namespace or manifest abuse | typed versioned manifests; closed schemas; ten-ability bound; collision refusal; double-hyphen-free IDs and a core-owned WordPress-compatible name encoding; no extension-selected transport or capability policy |
| Partial or failed extension registration | provider-local staging registry; atomic batch admission; exceptions and collisions discard that provider's complete batch while later valid providers and Kiln core continue |
| Extension capability privilege escalation | fixed core-owned `manage_options` gate; read-only/idempotent metadata; core-owned MCP discovery; REST disabled; executor rechecks authorization; no extension-supplied permission callback or write effect |
| Extension audit leakage | generic `kiln/extension` receipt class; bounded local receipts omit extension identity, input/output, callback, paths, and exception detail |
| Unrelated ability exposure | dedicated adapter server allow-lists Kiln names only; fixture proves unrelated/default-server abilities are absent |
| Subscriber transport discovery | dedicated server admission requires authenticated `edit_posts`; a subscriber is denied before ability discovery |
| Cross-origin credential abuse | HTTP negative fixture asserts no wildcard cross-origin response; Kiln adds no CORS override, proxy, or browser-facing credential channel |
| Protocol or session resource exhaustion | pinned adapter compatibility matrix; malformed/oversized fixture requests, fixed request counts, response-size cap, elapsed-time budget, and candidate-version gate |
| Upgrade-induced policy drift | explicit adapter-version input; candidate plus rollback suite proves policy data and receipt schemas retain their contract |
| Malicious or incomplete plugin artifact | local production-only release assembly, SHA-256 sidecar, required-entry and forbidden-file archive inspection, and manual checksum verification before upload |
| Operator diagnostic disclosure | manage_options local-only status page; four fixed allow-listed compatibility rows; no endpoint, credential, option, user, content, path, inventory, or stack-trace output |
| Unsafe deployment guidance | explicit staging, backup, HTTPS, least-privilege, revocation, no-fixture, and no-CORS-relaxation steps; no automatic deployment or rollback |
| Audit-log disclosure | local retention, allow-listed receipt fields, no identity name/ID, query/filter, content, URL, credential, or arbitrary error payload |
| Theme source/configuration disclosure | `edit_theme_options` execution check; four fixed read-only abilities; closed schemas; 20-item pages; allow-listed active-theme, template, template-part, and pattern metadata only; no markup/content, paths, CSS/PHP/theme.json, URLs, options, identities, credentials, diagnostics, plugin inventory, or global pattern inventory |
| Theme or sitewide visual mutation | no M10 write ability; no theme/template/part/pattern/menu/style/global-style/file/option operation; any future mutation requires an independently approved, approval-gated design |
| Resource exhaustion | schema limits, pagination, rate limits, timeout budgets, bounded batch size, 12,000-character single-read cap |

## Explicit exclusions

Kiln will not provide arbitrary PHP execution, shell execution, SQL execution, unrestricted filesystem access, generic REST forwarding, or unconstrained search-and-replace.