=== Kiln MCP ===
Contributors: garaged
Tags: mcp, model-context-protocol, development, security
Requires at least: 6.9
Requires PHP: 8.2
Requires Plugins: mcp-adapter
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A fully FLOSS, self-hosted, security-first MCP development toolkit for WordPress.

== Description ==

Kiln MCP exposes narrowly scoped WordPress development capabilities through the official WordPress MCP Adapter. It has no mandatory relay, account, telemetry, credential store, or custom transport.

Install the official MCP Adapter first. After activation, administrators can open Tools > Kiln MCP status to verify dependency compatibility. Use the repository operator guide for secure STDIO and HTTPS HTTP connection steps.

== Installation ==

1. Verify the release ZIP SHA-256 value published with the release.
2. In WordPress, install and activate the official MCP Adapter plugin.
3. Upload the Kiln MCP ZIP through Plugins > Add New > Upload Plugin, then activate it.
4. Open Tools > Kiln MCP status. Resolve every blocked compatibility check before using an MCP client.
5. Use STDIO for local development or the adapter's authenticated HTTP transport over HTTPS with a dedicated least-privileged WordPress user.

== Security ==

Kiln does not create or store client credentials, configure CORS, or expose a public diagnostic endpoint. Never use disposable test credentials in production. Revoke an Application Password when a client is retired or its device is lost.
