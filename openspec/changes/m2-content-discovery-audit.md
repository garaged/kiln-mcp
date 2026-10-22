# Change: M2 audited post discovery

## Why

The first useful WordPress-development workflow is discovering a small list of posts without granting a model arbitrary REST access or creating an unauditable read surface.

## Scope

- one bounded post-metadata discovery ability;
- `edit_posts` authorization;
- local redacted audit receipt persistence;
- schema, authorization, persistence, and real-WordPress tests.

## Constraints

- no post bodies, private content, broad search, arbitrary query parameters, or writes;
- no third-party audit sink, telemetry, custom transport, or relay;
- the adapter remains the sole MCP transport.
