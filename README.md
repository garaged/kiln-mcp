# Kiln MCP

**Kiln MCP** is a fully free and open-source, self-hosted Model Context Protocol (MCP) development toolkit for WordPress.

It gives WordPress developers a fast, extensible, and security-first way to expose narrowly scoped site capabilities to MCP clients—without a mandatory hosted relay, account, or paid service.

> Status: Milestone 3 in validation. Diagnostics, bounded discovery, and redacted single-post reading are available for acceptance testing.

## Principles

- **Direct by default.** The WordPress host serves the MCP endpoint. Optional relays must be separately deployable, FLOSS, and never required.
- **Least privilege.** Capabilities are explicit, individually authorized, and deny write access by default.
- **WordPress-native.** Kiln uses the WordPress Abilities API and requires the official MCP Adapter plugin; it does not implement a competing transport.
- **Composable, not omnipotent.** Small capability modules replace generic REST, database, and shell escape hatches.
- **Auditable.** Security-relevant actions produce redacted, locally retained audit evidence.
- **FLOSS.** GPL-2.0-or-later, no telemetry, no SaaS dependency, no proprietary runtime.

## Capabilities

| Ability | Authorization | Bound |
|---|---|---|
| `kiln/site-health-summary` | `manage_options` | fixed, allow-listed runtime summary |
| `kiln/list-posts` | `edit_posts` | maximum 20 public or authorized draft metadata records |
| `kiln/read-post` | `edit_post` for the exact post | one standard published/draft post as up to 12,000 characters of plain text |

The post reader excludes private, scheduled, trashed, password-protected, and custom-post-type content. It removes shortcodes and markup, reports truncation, and labels returned content as untrusted data. It does not expose user identity, comments, revisions, custom fields, media metadata, raw HTML, or generic post properties.

All discovery and post-read outcomes create a bounded, local redacted audit receipt. Receipts never retain request input, post identifiers/data/URLs, identity, credentials, or errors.

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

M3 is validating narrow, per-post content access. It deliberately excludes public unauthenticated access, relays, generic content/database access, and site-changing tools.

The current acceptance criteria live in [specs/requirements/m3-single-post-read.md](specs/requirements/m3-single-post-read.md).
