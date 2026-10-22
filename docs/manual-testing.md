# Manual testing

## Milestone 3

### Automated real-WordPress contract

Run:

    make container-preflight
    make container-test
    make container-down

Expected result: the runner installs a pinned MCP Adapter plugin, activates Kiln MCP, and proves all of the following in a real WordPress instance:

- an author can retrieve at most 20 public or self-authored draft metadata records;
- a permitted caller can read one exact-ID published post using only the M3 output contract;
- markup and registered shortcode output are removed from returned post text;
- a private post is denied even to an administrator;
- an author can read an owned draft;
- a subscriber is denied all three Kiln abilities; and
- successful and denied discovery/read attempts create only redacted local audit receipts.

### Local quality

Run after cloning:

    composer install
    make ci

Expected result: specification, architecture, coding-standard, static-analysis, and unit-test checks succeed.

### Clean-environment confirmation

On macOS Apple Silicon with Rancher Desktop and on Linux x86-64, record the operating system, CPU architecture, Rancher Desktop/Docker Engine and Compose versions, selected Docker context, successful container command output, and confirmation that only localhost port 8080 is published.

No production WordPress site, credentials, or Docker TCP listener may be used.
