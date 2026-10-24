# Change: M11 bounded page discovery and read

## Why

The current safe workflow can inspect posts but lacks equivalent context for WordPress pages, where most site landing and informational content resides. M11 provides the smallest useful page-read surface without anticipating page authoring or turning the plugin into generic content access.

## Scope

- two explicit read-only standard-page abilities;
- `edit_pages` list authorization and exact `edit_post` page-read authorization;
- closed schemas, 20-record page discovery, 12,000-character plain-text reads, redacted receipts, and official-adapter membership;
- unit, integration, MCP contract, container, CI, and Rancher Desktop evidence.

## Constraints

- WordPress and the official MCP Adapter retain platform and transport responsibility; Kiln owns only policy, schemas, bounds, records, redaction, and receipts;
- all unsupported page states fail closed;
- no raw markup, hierarchy, template assignment, source/path, configuration, identity, credential, diagnostic, global inventory, or mutation crosses the boundary;
- no relay, telemetry, credential store, custom transport, broad REST, filesystem, database, shell, or PHP execution is introduced.