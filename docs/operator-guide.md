# Production operator guide

## Before installation

Use a staging site first. Confirm the site runs WordPress 6.9+, PHP 8.2+, HTTPS for any HTTP MCP access, and the official MCP Adapter plugin. Kiln does not supply a relay, OAuth provider, credential store, CORS configuration, or automatic updates.

Download a Kiln release ZIP and its `.sha256` sidecar from the same trusted release. Verify it before uploading:

```sh
shasum -a 256 kiln-mcp-<version>.zip
cat kiln-mcp-<version>.zip.sha256
```

The hashes must match exactly. Do not install a ZIP from an untrusted mirror.

## Install and verify

1. Back up the site using its existing host or WordPress backup process.
2. Install and activate the official MCP Adapter.
3. Upload the verified Kiln ZIP in **Plugins → Add New → Upload Plugin**, then activate Kiln.
4. As an administrator, open **Tools → Kiln MCP status**.
5. Resolve every blocked check before connecting an MCP client. The page is read-only and contains no endpoint, credential, user, content, option, or filesystem data.
6. Confirm the dedicated `kiln-mcp` server is visible only to an authenticated WordPress user with `edit_posts`. Tool-specific restrictions continue to apply.

## Connect safely

For a local development machine, use the official adapter's documented WP-CLI STDIO server. It avoids exposing an HTTP endpoint solely for local development.

For HTTP:

- use a site with correctly configured HTTPS;
- create a dedicated WordPress user with only the least privilege needed for its intended Kiln capabilities;
- create a WordPress Application Password for that user and enter it only in the MCP client or the host-managed secret store;
- target Kiln's dedicated adapter server, never a generic REST forwarder;
- revoke the Application Password immediately when the client is retired, the device is lost, or access is no longer required.

Never use the disposable container test credentials, expose a development endpoint publicly, weaken WordPress authorization, add permissive CORS headers, or place a relay between the client and the site merely for convenience.

## Upgrade and rollback

1. Take a site backup.
2. In staging, install the new verified ZIP and run the status checks plus the normal MCP client smoke workflow.
3. When upgrading the official MCP Adapter, run the documented candidate-version and pinned-rollback container rehearsal before adopting the newer adapter.
4. In production, replace Kiln through the standard WordPress plugin updater or verified ZIP upload.
5. If the plugin behaves unexpectedly, deactivate it, restore the prior verified Kiln ZIP, and remove/revoke the affected client credential. Kiln does not perform automatic rollback.

MCP tool grants and WordPress policy remain WordPress-controlled; never treat a successful status view as authorization for a tool effect.
