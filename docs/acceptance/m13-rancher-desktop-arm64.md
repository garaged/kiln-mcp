# M13 Rancher Desktop ARM64 acceptance

## Status

Pending manual acceptance.

## Automated baseline

From the reviewed branch, run:

    make container-preflight
    make container-test
    make container-down

Expected result: preflight identifies the intended local Docker context and architecture; the isolated real-WordPress suite passes; teardown removes the disposable stack.

## Local administrator flow

Use only a disposable local WordPress site and an administrator account.

1. Open **Tools → Kiln MCP theme approvals**. Confirm installed-theme inventory contains no paths, URLs, screenshots, source, credentials, or plugin data.
2. Activate one installed fixture theme: issue an activation approval, execute the matching MCP action once, and confirm its prior active theme is recorded. Confirm reused, expired, altered-target, and altered-state tokens are refused.
3. Issue a rollback approval and restore only that recorded immediate prior theme. Confirm no unrelated theme can be selected as a rollback target.
4. For a WordPress.org slug, issue an install approval and execute it with the exact returned identity. Confirm a version change invalidates the approval. No remote URL may be entered or returned.
5. Upload a small known-safe local theme ZIP. Retain it only after the scan accepts it, issue installation approval with its opaque ID and digest, and execute the matching action once.
6. Confirm the ZIP workflow refuses traversal, links, oversized or expansive archives, unexpected executable files, and representative high-risk or obfuscated PHP. A refused archive cannot receive approval.
7. For one installed theme with an available official update, issue update approval and run the matching action once. Confirm no-update themes and changed identities are refused.
8. Repeat selected effect actions as a subscriber. Confirm every attempt is denied.
9. Confirm MCP responses, receipts, and operator pages reveal no archive content, source, path, URL, credential, PHP finding, or stack detail.

Do not use production credentials, a publicly exposed Docker listener, an arbitrary remote package URL, or an unreviewed PHP archive.
