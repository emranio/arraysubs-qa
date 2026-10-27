# TranslatePress compatibility verification

Scope confirmed 2026-10-28: install TranslatePress and verify database-saved
customer-facing display text in ArraySubs and ArraySubs Pro. WPML was explicitly
removed from scope by the user. This is a new focused cycle; no previous QA plan
or schedule is reused.

Test environment: local WordPress at http://localhost:10013, admin entry
http://localhost:10013/wp-admin/?localwp_auto_login=1, as documented in AGENTS.md.
Use isolated agent-browser sessions `trp-admin`, `trp-guest`, and `trp-customer`.
Use WP-CLI with --allow-root and the existing local database socket. No purchases,
paid machine translation, external email delivery, or live-site deployment.

Manual schedule for this session:

1. Install and configure TranslatePress, English source and Spanish target.
   Capture existing affected settings before creating test translations.
2. Baseline: save a distinctive custom add-to-cart label, translate it through
   TranslatePress, verify both language URLs, and confirm the source DB value.
3. Audit and fix customer JSON/JavaScript paths that normal HTML output buffering
   does not translate. Cover representative core and premium display payloads,
   dynamic changes, placeholders, and locale-specific reads without cache leaks.
4. Browser regression: product, cart, account and available premium customer flows;
   core-only operation; admin settings retain editable source text. Restore changed
   business settings and remove test fixtures while retaining TranslatePress.
5. User-approved extension: provide translation management directly in ArraySubs
   settings. Verify configured-language selection, search/group/pagination,
   source text remaining read-only, successful manual saves into TranslatePress,
   frontend changes without visiting every condition, permission/error handling,
   HTML/placeholders and stale-source guards. Reuse this cycle's temporary source
   fixtures for the new UI checks, then complete the cleanup in stage 4.

Track execution in the fresh kanban board in this directory. Implementation tasks
are issues #128–134. New defects belong in qa/issues with the required route,
user/product/subscription/order IDs (or N/A), steps and evidence. Record skipped
or blocked cases honestly; do not claim universal compatibility from a sample.

Screenshots and observations go in this directory. Site mutations are owned by
the root agent; implementation agents must not change the shared site.
