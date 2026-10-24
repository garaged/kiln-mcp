# Kiln MCP

**Kiln MCP** is a fully free and open-source, self-hosted Model Context Protocol (MCP) development toolkit for WordPress.

It gives WordPress developers a fast, extensible, and security-first way to expose narrowly scoped site capabilities to MCP clients—without a mandatory hosted relay, account, or paid service.

> Status: M0–M10 are delivered. Milestone 11 is in development: bounded page discovery and read.

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
| `kiln/publish-post` | `edit_post` plus local administrator approval | one current unprotected draft → published |
| `kiln/schedule-post-publication` | `edit_post` plus local administrator approval | one current unprotected draft → future UTC publication |
| `kiln/trash-post` | `edit_post` plus local administrator approval | one current unprotected draft/published post → WordPress Trash |
| `kiln/restore-post` | `edit_post` plus local administrator approval | one current unprotected trashed post → draft |
| `kiln/list-supported-post-types` | `manage_options` | at most 20 public post-type names, labels, and hierarchy flags |
| `kiln/list-supported-taxonomies` | `manage_options` | at most 20 public taxonomy names, labels, and hierarchy flags |
| `kiln/list-navigation-menus` | `edit_theme_options` | at most 20 menu IDs, names, and item counts; never items, URLs, or locations |
| `kiln/list-categories` / `kiln/list-tags` | `manage_categories` | one closed page of at most 20 fixed term records |
| `kiln/create-category` / `kiln/create-tag` | `manage_categories` | idempotent creation of one plain-text standard term; categories require an admitted parent |
| `kiln/get-active-theme-context` | `edit_theme_options` | active-theme metadata only |
| `kiln/list-block-templates` / `kiln/list-block-template-parts` | `edit_theme_options` | planned M10: closed pages of at most 20 metadata records |
| `kiln/list-active-theme-patterns` | `edit_theme_options` | active-theme-only pattern metadata |\n| `kiln/list-pages` | `edit_pages` | planned M11: maximum 20 standard published/draft page metadata records |\n| `kiln/read-page` | `edit_post` for the exact page | planned M11: one standard page as up to 12,000 characters of plain text |

The post reader excludes private, scheduled, trashed, password-protected, and custom-post-type content. It removes shortcodes and markup, reports truncation, and labels returned content as untrusted data. It does not expose user identity, comments, revisions, custom fields, media metadata, raw HTML, or generic post properties.

Lifecycle approvals are issued only from **Tools → Kiln MCP approvals** by a `manage_options` administrator. Kiln stores only an approval hash, exact action/target/version binding, expiry, and consumed state; tokens expire in ten minutes, are one-time, and are never issued or returned through MCP.

All discovery, authoring, lifecycle, site-structure, and theme-context outcomes create a bounded, local redacted audit receipt. Receipts never retain request input, identifiers/data/URLs, identity, credentials, or errors.

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

## Release artifact

On Rancher Desktop or any Docker host, assemble and inspect a production ZIP without host PHP tooling:

    make release-container-check
    make release-container

This writes the installable ZIP and SHA-256 sidecar to `dist/`. The host-native `make release` target is retained for systems that already have Composer 2 and `zip` installed.

## Status and roadmap

The [product roadmap](docs/roadmap.md) tracks delivered milestones, the current capability bundle, remaining feature groups, and their security exit gates.

M12's acceptance criteria are in [specs/requirements/m12-page-drafting.md](specs/requirements/m12-page-drafting.md). It adds bounded creation and optimistic-concurrency revision of standard page drafts only; publishing and structural page mutations remain excluded.

M10's acceptance criteria are in [specs/requirements/m10-theme-context-discovery.md](specs/requirements/m10-theme-context-discovery.md). It adds read-only, bounded active-theme planning context without exposing theme source, configuration, files, URLs, patterns outside the active theme, or any theme/site mutation.