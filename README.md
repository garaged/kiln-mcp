# Kiln MCP

**Kiln MCP** is a fully free and open-source, self-hosted Model Context Protocol (MCP) development toolkit for WordPress.

It gives WordPress developers a fast, extensible, and security-first way to expose narrowly scoped site capabilities to MCP clients—without a mandatory hosted relay, account, or paid service.

> Status: Milestone 2 in development. The first diagnostic capability is available; bounded, audited content discovery is being validated.

## Principles

- **Direct by default.** The WordPress host serves the MCP endpoint. Optional relays must be separately deployable, FLOSS, and never required.
- **Least privilege.** Capabilities are explicit, individually authorized, and deny write access by default.
- **WordPress-native.** Kiln uses the WordPress Abilities API and requires the official MCP Adapter plugin; it does not implement a competing transport.
- **Composable, not omnipotent.** Small capability modules replace generic REST, database, and shell escape hatches.
- **Auditable.** Security-relevant actions must produce redacted, locally retained audit evidence.
- **FLOSS.** GPL-2.0-or-later, no telemetry, no SaaS dependency, no proprietary runtime.

## Available capability

`kiln/site-health-summary` is discoverable through the official adapter, but only a WordPress identity with `manage_options` can execute it. Its result is deliberately limited to Kiln/WordPress/PHP major-minor versions, multisite state, active-theme stylesheet, and `available` status.

It does not expose site title or URL, users, credentials, options, plugin inventory, database details, filesystem paths, or arbitrary server diagnostics.

## In-progress capability: bounded post discovery

Milestone 2 adds `kiln/list-posts` for identities with `edit_posts`. It accepts only a post status (`publish` or `draft`), a 1–20 result limit, and a positive page number. It returns only post ID, title, status, modification time, and permalink; it never returns bodies, private posts, arbitrary query filters, or bulk output. Authors without `edit_others_posts` are restricted to their own drafts.

Every successful or denied discovery attempt stores a bounded, local receipt containing only schema version, timestamp, ability name, outcome, caller capability class, and result count. It never retains request input, content, URLs, user identity, or credentials.

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

Milestone 1 delivered a single, safe, end-to-end diagnostic capability. Milestone 2 is implementing bounded, read-only post metadata discovery with redacted local audit receipts. Both deliberately exclude public unauthenticated access, relays, generic content/database access, and site-changing tools.

The current acceptance criteria live in [specs/requirements/m2-content-discovery-audit.md](specs/requirements/m2-content-discovery-audit.md).
