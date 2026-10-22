# Contributing to Kiln MCP

Kiln MCP is built with intent-, specification-, and test-driven development.

## Before code

1. Read the constitution, applicable requirement, architecture, ADRs, threat model, and testing strategy.
2. Add or update an OpenSpec change under openspec/changes/.
3. Add acceptance criteria and traceability before implementation.
4. Identify the tool effect class and user, WordPress-capability, and policy checks.

## Pull requests

Use one coherent milestone branch and draft PR. A PR must include requirements and registry updates, architecture or ADR updates when consequential, tests at suitable layers, manual acceptance evidence, security/privacy impact, and passing make ci.

Do not merge a PR without explicit approval. Never include real secrets, production exports, or unredacted logs.

## Compatibility

The plugin targets WordPress 6.9+ and PHP 8.2+. The official WordPress MCP Adapter is the protocol integration foundation; Kiln owns its capability and policy layer.
