# Manual testing

## Milestone 0

Run after cloning:

    composer install
    make ci

Expected result: specification, architecture, coding-standard, static-analysis, and unit-test checks succeed.

Then verify in an isolated WordPress 6.9+ / PHP 8.2+ site:

1. Copy the repository as wp-content/plugins/kiln-mcp and run composer install --no-dev.
2. Activate Kiln MCP in wp-admin.
3. Confirm activation has no warning or fatal error.
4. Confirm no MCP management/write tools are exposed yet.
5. Confirm the plugin makes no outbound network request and creates no telemetry/account configuration.
6. Deactivate and reactivate the plugin; repeat steps 3–5.

Record environment versions and command output in the PR Validation evidence.
