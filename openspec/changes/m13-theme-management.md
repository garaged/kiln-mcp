# M13: approval-gated theme management

## Change
Add a small, explicit theme-management capability bundle: inventory, approval-gated WordPress.org or locally uploaded ZIP installation/update, exact activation, health-checked rollback to the previously active installed theme, and redacted local audit evidence.

## Constraints
- no arbitrary network URL or filesystem source;
- no MCP ZIP upload, raw archive, theme source, PHP/CSS, or paths;
- every locally uploaded ZIP is fail-closed scanned for unsafe archive structure and high-risk PHP execution/obfuscation patterns before it is eligible for approval;
- no bulk operation, delete, automatic update, plugin management, or global-style mutation;
- no effect without one-time local administrator approval; and
- failed activation must preserve/recover the prior active theme where WordPress can do so safely.
