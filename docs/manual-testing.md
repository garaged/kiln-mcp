# Manual testing

## Milestone 4

### Automated real-WordPress contract

Run:

    make container-preflight
    make container-test
    make container-down

Expected result: the runner installs a pinned MCP Adapter plugin, activates Kiln MCP, and proves all of the following in a real WordPress instance:

- an author can create one owned standard-post draft using only bounded plain-text input;
- an author can update that exact draft using its current modification token;
- a stale token is refused without overwriting the newer draft;
- a draft update cannot publish or alter the target's fixed draft status;
- an editor can update an admitted draft, while a subscriber is denied all Kiln abilities;
- private/custom/non-draft targets, markup-like input, and unknown fields are refused; and
- successful and denied authoring attempts create only redacted local audit receipts.

### Local quality

Run after cloning:

    composer install
    make ci

Expected result: specification, architecture, coding-standard, static-analysis, and unit-test checks succeed.

### Clean-environment confirmation

On macOS Apple Silicon with Rancher Desktop and on Linux x86-64, record the operating system, CPU architecture, Rancher Desktop/Docker Engine and Compose versions, selected Docker context, successful container command output, and confirmation that only localhost port 8080 is published.

No production WordPress site, credentials, or Docker TCP listener may be used.
