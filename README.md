# Kiln MCP

**Kiln MCP** is a fully free and open-source, self-hosted Model Context Protocol (MCP) development toolkit for WordPress.

It gives WordPress developers a fast, extensible, and security-first way to expose narrowly scoped site capabilities to MCP clients—without a mandatory hosted relay, account, or paid service.

> Status: Milestone 0 foundation. No WordPress management tools are exposed yet.

## Principles

- **Direct by default.** The WordPress host serves the MCP endpoint. Optional relays must be separately deployable, FLOSS, and never required.
- **Least privilege.** Capabilities are explicit, individually authorized, and deny write access by default.
- **WordPress-native.** Kiln builds on the WordPress Abilities API and the official MCP Adapter rather than maintaining a competing protocol implementation.
- **Composable, not omnipotent.** Small capability modules replace generic REST, database, and shell escape hatches.
- **Auditable.** Security-relevant actions must produce redacted, locally retained audit evidence.
- **FLOSS.** GPL-2.0-or-later, no telemetry, no SaaS dependency, no proprietary runtime.

## Development

Prerequisites: PHP 8.2+, Composer 2, and Make.

```sh
composer install
make check
```

See [CONTRIBUTING.md](CONTRIBUTING.md), [docs/constitution.md](docs/constitution.md), and [docs/architecture.md](docs/architecture.md).

## Status and roadmap

Milestone 0 establishes governance, an executable policy nucleus, static analysis, unit tests, and CI. It deliberately does **not** expose MCP tools or change a WordPress site.

The roadmap and acceptance criteria live in [specs/requirements/m0-foundation.md](specs/requirements/m0-foundation.md).
