# ADR 0008: expose active-theme metadata without template source or mutation

## Status

Accepted for M10.

## Context

Safe WordPress development needs enough theme context to avoid blind recommendations, yet templates, patterns, theme configuration, and files can contain active content, implementation details, or injected instructions. Generic theme APIs and full pattern registries would expose too much.

## Decision

Kiln registers four explicit read-only abilities protected by `edit_theme_options`. The active-theme ability returns only a fixed identity and capability summary. Template, template-part, and pattern abilities return only closed, paged metadata records, with a maximum of 20 records per page. Pattern discovery filters to the active theme.

Kiln does not return markup, content, files, paths, configuration, URLs, identities, credentials, diagnostics, or global inventories. It records only redacted receipt classes and has no M10 write path. Unsupported themes are represented by a stable bounded state or empty result.

## Consequences

Clients gain enough design-system awareness to plan accurately while the site retains full control over source, configuration, and effects. A future ability to alter a template or pattern remains a distinct, approval-gated design rather than an extension of M10.