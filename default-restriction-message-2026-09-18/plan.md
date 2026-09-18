# Global restriction message verification

Scope confirmed by user: expose the global rich-text restriction message at the start of Content Gate, use it for empty content-restriction messages (including login-only), preserve custom overrides. Site: http://localhost:10003. Admin: local auto-login user 1. Guest: isolated agent-browser session. Existing QA issues are preserved.

Schedule: 2026-09-18 after implementation/build, in the following order. No recurring watcher.

| Task | Checks |
| --- | --- |
| DM-1 | First jump tab/section, alert, rich editor, save/loading/success, reload, narrow layout |
| DM-2 | Global vs custom Gutenberg, Elementor, shortcode, per-post and CPT browser fixtures; denied vs authorized visitors |
| DM-3 | Global vs custom URL/message/403 and comment notices; active per-post rule ignores overlapping CPT message |
| DM-4 | Change global: blank gates update, custom gates stay custom; blank global and empty rich HTML; sanitization; admin-only endpoint |
| DM-5 | Confirm partial save preserves other settings/rules; production build, source review, line counts, restore original default and remove fixtures |

Use real browser settings save/edit interactions and frontend checks; WP-CLI only for fixture setup, readback, supplemental assertions and cleanup. All WP-CLI commands use --allow-root and the existing Local MySQL socket. No phpcs or dependency installation.
