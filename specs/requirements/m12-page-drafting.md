# M12 requirements: safe page drafting

## REQ-M12-001 — bounded page-draft creation
Kiln shall expose a closed `kiln/create-page-draft` ability that requires `edit_pages` and creates exactly one active-user-owned, standard, unprotected page with status `draft`, a bounded plain-text title, and bounded plain-text content.

## REQ-M12-002 — exact optimistic page-draft update
Kiln shall expose a closed `kiln/update-page-draft` ability that accepts only page ID, expected modification token, title, and content; requires exact `edit_post`; admits only unprotected standard page drafts; refuses stale tokens; and preserves workflow state.

## REQ-M12-003 — restricted effects and outputs
Both abilities shall expose fixed metadata only and shall not accept or modify publication state, hierarchy, template, slug, author, comments, metadata, taxonomy, media, password, markup, shortcodes, or bulk targets.

## REQ-M12-004 — audit and validation
Every allow or denial decision shall append a local redacted receipt. Unit and real-WordPress tests shall prove closed schemas, authorization, stale-write refusal, state preservation, and subscriber denial.

## REQ-M12-005 — acceptance
Linux CI and a documented Rancher Desktop ARM64 run shall pass before the milestone is review-ready.
