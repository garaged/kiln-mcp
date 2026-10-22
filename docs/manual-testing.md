# Manual testing

## Milestone 0

### Automated real-WordPress smoke

Run:

    make container-preflight
    make container-test
    make container-down

Expected result: the container runner installs WordPress, activates Kiln MCP, reaches the REST index, and proves that M0 exposes no Kiln MCP namespace. The environment is disposable; cleanup removes its database and WordPress volumes.

### Local quality

Run after cloning:

    composer install
    make ci

Expected result: specification, architecture, coding-standard, static-analysis, and unit-test checks succeed.

### Clean-environment confirmation

On macOS Apple Silicon with Rancher Desktop and on Linux x86-64, record the operating system, CPU architecture, Rancher Desktop/Docker Engine and Compose versions, selected Docker context, successful container command output, and confirmation that only localhost port 8080 is published.

No production WordPress site, credentials, or Docker TCP listener may be used.
