# Manual testing

## Milestone 11

### Automated real-WordPress contract

Refresh the reviewed branch, then run:

    git fetch origin
    git switch feature/m11-page-discovery-read
    git pull --ff-only origin feature/m11-page-discovery-read
    make container-preflight
    make container-test
    make container-down

Expected result: the isolated runner proves an administrator can list only bounded standard published/draft pages and read one admitted page as bounded plain text; private/future/malformed requests are refused; page hierarchy, template, author, comments, revisions, custom fields, media, raw markup, and shortcodes are absent; a subscriber is denied; and before/after state is unchanged. This automated path publishes no host browser port, so an unrelated local service using port 8080 cannot affect it.

## Milestone 10

### Automated real-WordPress contract

Refresh the reviewed branch, then run:

    git fetch origin
    git switch feature/m10-theme-context-discovery
    git pull --ff-only origin feature/m10-theme-context-discovery
    make container-preflight
    make container-test
    make container-down

Expected result: the isolated runner activates a deterministic local block-theme fixture and proves that a site administrator can receive only the fixed theme metadata, template metadata, template-part metadata, and pattern metadata. It also proves that every page is capped at 20 items; source content and paths are absent; unknown input and oversize pages are refused; and a subscriber is denied every M10 ability.

### Local administrator check

1. Use a disposable local WordPress instance with an administrator account.
2. Confirm the active theme is the intended block theme, then run the four M10 read-only abilities through the configured MCP Adapter.
3. Verify that results contain only their documented fields: no HTML, CSS, PHP, `theme.json`, filesystem path, URL, option, user, credential, or plugin data.
4. Confirm no theme, template, pattern, menu, style, global-style, file, or option is modified.
5. Repeat as a subscriber and confirm every M10 ability is denied.

## Milestone 9

### Release artifact and operator status

Run:

    make release-container-check
    make container-preflight
    make container-test
    make container-down

Expected result: the containerized release gate builds a production-only ZIP in a temporary directory without requiring host Composer or a host zip utility, validates its SHA-256 sidecar, loads the packaged Kiln composition root through its own autoloader, and rejects development-only content. The real-WordPress smoke suite also proves that the non-root WordPress web user can create and remove a probe in `wp-content/uploads`, then renders **Tools → Kiln MCP status** for an administrator and verifies the four allow-listed compatibility rows without a credential, URL, or filesystem-path disclosure.

### Rancher Desktop acceptance

1. Use a disposable local WordPress environment with Rancher Desktop on macOS Apple Silicon.
2. Run the command sequence above and record the Docker context, daemon architecture, Compose version, and successful output.
3. Build a local artifact with make release-container; this is the supported Rancher Desktop path and needs no host Composer or zip utility. Verify the printed checksum using shasum -a 256 before uploading it to the disposable site. make release remains available for hosts that already provide Composer and zip.
4. Start a freshly bootstrapped disposable site with `make container-up`, then upload through **Plugins → Add New → Upload Plugin** at http://127.0.0.1:8080/wp-admin. If port 8080 is occupied, use `KILN_MCP_TEST_PORT=8081 make container-up` and visit that port instead. If the stack existed before an updated bootstrap, run make container-down first so its volumes are recreated. Because the smoke fixture already contains Kiln from the checked-out source, choose WordPress’s standard **Replace current with uploaded** action if it detects that installed fixture. The disposable administrator is `kiln-admin`; its known test password is documented only by the bootstrap fixture. Do not reuse it anywhere.
5. Alternatively, copy the verified ZIP into the disposable WordPress container and force-install it with WP-CLI using the project name and compose file from the Makefile.
6. Open **Tools → Kiln MCP status** as an administrator. Confirm all four checks are ready and the page discloses no endpoint, credential, content, user, option, filesystem-path, or plugin-inventory data.
7. Run make container-down and remove the local artifact after verification. Do not use production credentials, relax CORS, or expose the disposable environment publicly.


## Milestone 8

### Automated protocol contract

Run:

    make container-preflight
    make container-test
    make container-down

Expected result: M1–M7 regression coverage remains green. M8 additionally proves the dedicated `kiln-mcp` official-adapter server over STDIO and authenticated HTTP: initialization, tool discovery, Kiln ability discovery/schema inspection, authorized execution, unauthenticated and subscriber refusal, malformed request refusal, non-wildcard cross-origin headers, fixed response-size and elapsed-time budgets.

### Upgrade and rollback rehearsal

1. Use only the disposable environment and its generated test credentials.
2. When a release newer than the pinned baseline is available, run `MCP_ADAPTER_VERSION=<candidate> make container-test`, followed by `make container-down`.
3. Roll back by running `make container-test` with no version override, followed by `make container-down`; the pinned baseline is `0.7.1`.
4. Confirm both runs report the full protocol matrix as passed.
5. Confirm no raw application password appears in terminal output, Git history, option values, audit receipts, or support artifacts.
6. Record the pinned and candidate adapter versions, protocol revision, Docker context, daemon architecture, Compose version, and successful output in the release change or issue that proposes adoption.

Do not fabricate this evidence by selecting an equal or older version. Until a newer release exists, the pinned-baseline suite and its Linux CI/Rancher Desktop records are the M8 compatibility evidence.

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
6. Confirm `make container-test` publishes no host port. `make container-up` may publish only the selected loopback browser port. Do not use production credentials, a public Docker listener, or a real extension with unreviewed PHP.

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

On macOS Apple Silicon with Rancher Desktop and on Linux x86-64, record the operating system, CPU architecture, Rancher Desktop/Docker Engine and Compose versions, selected Docker context, successful container command output, and confirmation that automated tests publish no host port (or that manual browser inspection uses only the selected loopback port).

No production WordPress site, credentials, or Docker TCP listener may be used.

## Milestone 12

Run the standard automated contract:

    make container-preflight
    make container-test
    make container-down

Expected result: an authorized user can create one active-user-owned standard page draft and revise that exact draft only with its current opaque modification token. The suite refuses stale tokens, markup, unknown fields, subscriber access, publication, and structural fields such as hierarchy, template, slug, author, metadata, taxonomy, media, and password. It leaves every non-draft page unchanged.
