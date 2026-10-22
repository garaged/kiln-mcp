# M2 intent: audited post discovery

## Outcome

A developer-facing agent can list a small, explicitly filtered set of non-private post metadata through the standard adapter, while the site owner retains local, privacy-preserving evidence of every attempt.

## Capability contract

`kiln/list-posts` accepts only:

- `status`: `publish` or `draft`;
- `limit`: 1 through 20;
- `page`: positive integer.

It returns only ID, title, status, modified time, and permalink for standard posts. It never returns post body, author identity, comments, revisions, private posts, or custom-post-type data.

## Audit boundary

The receipt records timestamp, ability ID, allow/deny outcome, caller capability class, result count, and a fixed schema version. It excludes identity names/IDs, request filters, post data, URLs, credentials, and arbitrary errors.

## Exit decision

M2 exits with container proof of the capability’s authorization matrix, result bounds, and receipt redaction. M3 can consider a separately approved single-post read contract with content redaction rules.
