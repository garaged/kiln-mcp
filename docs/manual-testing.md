# Manual testing

## Milestone 8

### Automated protocol contract

Run:

    make container-preflight
    make container-test
    make container-down

Expected result: M1–M7 regression coverage remains green. M8 additionally proves the dedicated `kiln-mcp` official-adapter server over STDIO and authenticated HTTP: initialization, tool discovery, Kiln ability discovery/schema inspection, authorized execution, unauthenticated and subscriber refusal, malformed request refusal, non-wildcard cross-origin headers, fixed response-size and elapsed-time budgets.

### Upgrade and rollback rehearsal

1. Use only the disposable environment and its generated test credentials.
2. Run the documented candidate adapter-version target, then the pinned-version rollback target.
3. Confirm both runs report the full protocol matrix as passed.
4. Confirm no raw application password appears in terminal output, Git history, option values, audit receipts, or support artifacts.
5. Record the pinned and candidate adapter versions, protocol revision, Docker context, daemon architecture, Compose version, and successful output.

### Local connection boundary

Use the adapter's documented WP-CLI STDIO server for a local site. For HTTP, use HTTPS and a dedicated least-privileged WordPress user with an Application Password. Target Kiln's dedicated server endpoint, never a generic WordPress REST forwarder. Do not expose a development test endpoint publicly, use production credentials, or add browser CORS exceptions.

## Milestone 7

### Automated real-WordPress contract

Run:

    make container-preflight
    make container-test
    make container-down

Expected result: M1–M6 regression coverage remains green. M7 additionally activates a disposable valid local extension and a provider that throws after staging a manifest. The valid `kiln-ext/fixture--facts` ability is registered and administrator-executable; the partial throwing ability is absent; subscribers are denied; and redacted receipts retain no extension ID, input, output, callback, path, or exception detail.

### Rancher Desktop acceptance

1. Use a non-production WordPress environment and ensure Rancher Desktop is running.
2. Run the automated command sequence above from this repository.
3. Confirm container-preflight reports the intended Rancher Desktop context and native architecture.
4. Confirm container-test reports Container smoke checks passed.
5. Record the Docker context, daemon architecture, Compose version, and successful output.
6. Confirm no container port other than the loopback-bound disposable WordPress test port is published. Do not use production credentials, a public Docker listener, or a real extension with unreviewed PHP.

## Milestone 6

### Automated real-WordPress contract

Run:

    make container-preflight
    make container-test
    make container-down

Expected result: M1–M5 regression coverage remains green, and M6 additionally proves fixed-field bounded discovery of public post types, public taxonomies, navigation-menu metadata, categories, and tags; idempotent root/child category and tag seeding; rejection of markup, unknown fields, invalid parents, and oversized pages; subscriber denial; and redacted allowed and denied receipts.

### Security boundary confirmation

1. Sign in as an administrator on a disposable local site.
2. Invoke the seven M6 abilities using their documented closed inputs.
3. Confirm list outputs contain only their specified fields, at most 20 records, and that menu output contains no items, URLs, or locations.
4. Create the same category or tag twice and confirm the same term ID is returned.
5. Confirm there is no Kiln ability to create, update, delete, assign, reorder, or relocate navigation menus or menu items.
6. Record the Docker context, architecture, Compose version, and successful test output. Do not use a production site or credentials.

## Milestone 5

### Automated real-WordPress contract

Run:

    make container-preflight
    make container-test
    make container-down

Expected result: M1–M4 regression coverage remains green, and M5 additionally proves that an exact current draft can be published or scheduled; a published post can be moved to WordPress Trash then restored as a draft; a consumed approval cannot be replayed; raw approval values are absent from local approval storage; and a subscriber is denied every workflow ability.

### Local administrator flow

1. Sign in as a WordPress administrator and open **Tools → Kiln MCP approvals**.
2. Select the exact action and enter one admitted standard-post ID. The page refuses an unavailable source state.
3. Copy the displayed token immediately. It expires after ten minutes and is not shown again.
4. Use it only with the matching M5 ability, target ID, and current `modified_token` from `kiln/read-post`.
5. Confirm that reuse, a changed post version, a different action or ID, and an expired token are refused. No permanent deletion route exists.

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