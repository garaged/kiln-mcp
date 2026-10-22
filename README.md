# Kiln MCP

**Kiln MCP** is a fully free and open-source, self-hosted Model Context Protocol (MCP) development toolkit for WordPress.

It gives WordPress developers a fast, extensible, and security-first way to expose narrowly scoped site capabilities to MCP clients—without a mandatory hosted relay, account, or paid service.

> Status: Milestone 1. The first administrator-only, read-only diagnostic capability is available.

## Principles

- **Direct by default.** The WordPress host serves the MCP endpoint. Optional relays must be separately deployable, FLOSS, and never required.
- **Least privilege.** Capabilities are explicit, individually authorized, and deny write access by default.
- **WordPress-native.** Kiln uses the WordPress Abilities API and requires the official MCP Adapter plugin; it does not implement a competing transport.
- **Composable, not omnipotent.** Small capability modules replace generic REST, database, and shell escape hatches.
- **Auditable.** Security-relevant actions must produce redacted, locally retained audit evidence.
- **FLOSS.** GPL-2.0-or-later, no telemetry, no SaaS dependency, no proprietary runtime.

## First capability

`kiln/site-health-summary` is discoverable through the official adapter, but only a WordPress identity with `manage_options` can execute it. Its result is deliberately limited to Kiln/WordPress/PHP major-minor versions, multisite state, active-theme stylesheet, and `available` status.

It does not expose site title or URL, users, credentials, options, plugin inventory, database details, filesystem paths, or arbitrary server diagnostics.

Install and activate [MCP Adapter](https://wordpress.org/plugins/mcp-adapter/) before activating Kiln. For local clients, use the adapter's documented WP-CLI/STDIO server. For remote access, use its authenticated HTTP transport over HTTPS; Kiln does not add a relay.

## Development

Prerequisites: PHP 8.2+, Composer 2, Make, and the official MCP Adapter plugin for real WordPress testing.

    composer install
    make check

## Real WordPress container tests

The portable test path is Docker Compose. It supports Rancher Desktop on macOS and Docker Compose on Linux, uses native multi-architecture images, and binds the test site to loopback only.

    make container-preflight
    make container-test
    make container-down

See [the container-test guide](docs/testing-environment.md) for Rancher Desktop setup, architecture, troubleshooting, and command details.

## Status and roadmap

Milestone 1 adds a single, safe, end-to-end diagnostic capability. It deliberately excludes content access, configuration UI, audit persistence, public unauthenticated access, relays, and all site-changing tools.

The roadmap and acceptance criteria live in [specs/requirements/m1-read-only-diagnostics.md](specs/requirements/m1-read-only-diagnostics.md).
