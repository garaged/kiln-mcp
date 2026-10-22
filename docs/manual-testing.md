# Manual testing

## Milestone 2

### Automated real-WordPress contract

Run:

    make container-preflight
    make container-test
    make container-down

Expected result: the runner installs a pinned MCP Adapter plugin, activates Kiln MCP, confirms the diagnostic and discovery abilities are registered, and proves all of the following in a real WordPress instance:

- an author can retrieve at most 20 public or self-authored draft metadata records;
- the response contains only ID, title, status, modification time, and permalink;
- private posts and another user's draft are not exposed;
- a subscriber is denied both capabilities; and
- successful and denied discovery attempts create only redacted local audit receipts.

### Local quality

Run after cloning:

    composer install
    make ci

Expected result: specification, architecture, coding-standard, static-analysis, and unit-test checks succeed.

### Clean-environment confirmation

On macOS Apple Silicon with Rancher Desktop and on Linux x86-64, record the operating system, CPU architecture, Rancher Desktop/Docker Engine and Compose versions, selected Docker context, successful container command output, and confirmation that only localhost port 8080 is published.

No production WordPress site, credentials, or Docker TCP listener may be used.
