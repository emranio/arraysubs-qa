# Restricted message editor verification

Scope: Gutenberg restricted-content block, Elementor content restrictions, CPT access rules, and per-post access-rule message editor. User-specified site: http://localhost:10003. Admin: local auto-login, user 1. Guest: isolated browser session.

Schedule: 2026-09-18, after implementation and the core asset build. Existing qa/issues tasks remain unchanged.

1. RM-1: Edit formatted Gutenberg denial message, publish, reload editor, verify guest HTML and protected-content absence.
2. RM-2: Edit Elementor WYSIWYG message, publish, reload, verify guest HTML.
3. RM-3: Create isolated CPT rule, format message, save/reload, verify guest output. Check unrelated rule settings survive edits and discard works.
4. RM-4: Verify per-post restriction editor and saved message, including modal reopening.
5. RM-5: Verify basic tags, links, paragraphs/lists, unsafe HTML filtering, empty fallback, authorized content, core-only operation, console errors and cleanup.

Use browser interactions for the user-facing workflows; supplement with WP-CLI for fixtures and sanitization checks. Capture and inspect screenshots. No phpcs or expensive linting.
