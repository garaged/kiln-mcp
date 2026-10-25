# M13 intent: approval-gated theme management

## Outcome
Allow an MCP client to manage a WordPress theme safely through the same local-administrator approval boundary used for post lifecycle effects.

## Allowed sources
- an already installed theme;
- an identified WordPress.org theme slug; and
- an administrator-supplied ZIP uploaded through the local WordPress administration workflow.

No remote ZIP URL, arbitrary download URL, filesystem path, shell command, or PHP source is accepted.

## Abilities
- discover bounded installed-theme and update metadata;
- request a local approval for one exact install, update, activation, or rollback action;
- perform only the approved exact action; and
- restore the previously active installed theme after failed health validation.

## Safety contract
Each effect is bound to the action, theme stylesheet, selected artifact/version identity, and current state. Approval is issued only by a `manage_options` administrator, expires quickly, and is single-use. ZIPs are never accepted over MCP: an administrator uploads them locally, Kiln retains only bounded artifact metadata and a digest, and the write executor rechecks all preconditions. Theme source, PHP, CSS, credentials, paths, remote URLs, and archive contents are never returned through MCP.
