# Change: M10 bounded theme-context discovery

## Why

A development client needs a safe view of the active visual system before it can make useful WordPress recommendations. Existing Kiln discovery intentionally stops short of theme internals; M10 fills that planning gap without turning the plugin into a filesystem, configuration, or sitewide-write gateway.

## Scope

- four explicit read-only active-theme context, template, template-part, and pattern abilities;
- `edit_theme_options` execution authorization, closed schemas, and maximum 20-item pagination;
- allow-listed metadata only, active-theme pattern filtering, redacted receipts, and official-adapter membership;
- unit, integration, MCP contract, container, CI, and Rancher Desktop evidence.

## Constraints

- WordPress and the official MCP Adapter retain platform and transport responsibility; Kiln owns only policy, schemas, bounds, records, and receipts;
- classic or unsupported themes return a stable bounded state or empty discovery result;
- no content/markup, paths, configuration, credentials, identities, URLs, diagnostics, global inventory, or mutation may cross the boundary;
- no relay, telemetry, credential store, custom transport, broad REST, filesystem, database, shell, or PHP execution is introduced.