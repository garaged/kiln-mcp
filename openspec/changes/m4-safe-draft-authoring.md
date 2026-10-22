# Change: M4 safe draft authoring

## Why

Read-only tools are useful for inspection but do not complete a development loop. The next controlled increment supports creating and revising drafts while keeping consequential content workflow actions outside the capability boundary.

## Scope

- one standard-post draft creation ability;
- one exact-ID, optimistic-concurrency draft update ability;
- fixed plain-text inputs and fixed metadata/token outputs;
- per-post WordPress authorization plus explicit Kiln admission policy;
- local redacted receipts and a durable modification-token contract;
- unit and real-WordPress contract coverage.

## Constraints

- all created content is owned by the active user and has fixed `draft` status;
- an update never alters status, author, taxonomy, custom fields, media, or options;
- a stale modification token is refused, never overwritten;
- no arbitrary HTML/PHP, generic REST forwarding, custom transport, relay, or remote audit sink;
- publishing, scheduling, trash/restore, and bulk changes remain M5 work.
