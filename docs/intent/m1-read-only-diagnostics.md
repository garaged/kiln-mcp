# M1 intent: a safe first MCP capability

## Outcome

A site administrator running the official MCP Adapter can discover and execute one Kiln-owned, read-only diagnostic ability through the standard WordPress Abilities API.

## User intent

A developer needs a minimal, trustworthy confirmation that Kiln and the WordPress runtime are compatible before allowing an agent to perform any useful development task.

## Capability contract

`kiln/site-health-summary` accepts no input and reports only:

- Kiln, WordPress, and PHP major/minor versions;
- whether the site is multisite;
- the active theme stylesheet identifier;
- the constant status `available`.

It never reveals a site URL or title, users, credentials, installed-plugin inventory, options, database details, filesystem paths, or arbitrary diagnostics.

## Security boundary

`meta.mcp.public` allows the adapter to discover the ability; it does not grant anonymous execution. The adapter transport authenticates the WordPress identity, and Kiln independently requires `manage_options`. The callback has no mutation path.

## Exit decision

M1 exits when the real WordPress test proves registration, allowed execution, denied execution, and exact allow-listed output. M2 may add local audit receipts and a first developer-content read capability.
