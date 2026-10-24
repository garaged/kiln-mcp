# M12: safe page drafting

## Change
Add two narrowly bounded write abilities for standard page drafts, following M4's proven draft-only optimistic-concurrency contract while retaining M11's page admission boundary.

## Requirements
1. Create only active-user-owned standard page drafts from bounded plain text.
2. Update only exact, unprotected standard page drafts with a matching opaque modification token.
3. Use closed schemas and fixed allow-listed output metadata.
4. Refuse all lifecycle, hierarchy, template, slug, metadata, taxonomy, media, password, markup, shortcode, and bulk effects.
5. Test authorization, stale-write refusal, state preservation, audit redaction, and ARM64 Rancher Desktop behavior.
