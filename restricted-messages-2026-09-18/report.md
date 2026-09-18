# Restricted message rich-text editors — verified

Tested 2026-09-18 on **http://localhost:10003**, using the requested local admin auto-login (user 1, administrator) and isolated guest browsers. All planned tasks passed.

## Implementation

- Gutenberg Restricted Content, CPT access rules, and per-post Access Restriction use the shared TinyMCE field with bold, italic, ordered/unordered lists, links, and clear formatting.
- Elementor uses its native WYSIWYG control, including Visual/Code editing.
- Messages render with `wpautop()` and `wp_kses_post()`, preserving safe HTML without escaped tags or invalid nested paragraphs.
- Shared editor IDs are unique, callbacks stay current as rules change, and absolute links retain their entered URLs.
- Per-post modal link dialogs appear above the WordPress modal.
- Work belongs to core; no Pro source changes were required. Initial browser checks ran with Pro inactive. Later checks also ran with Pro active, and the supplemental rendering checks passed again with Pro explicitly skipped.

## Browser results

| Task | Result | Evidence |
| --- | --- | --- |
| RM-1 Gutenberg | Typed and formatted message, used list and link controls, published, reloaded, confirmed saved HTML and guest restriction. Clearing the editor restored the login fallback. | [Editor](evidence/gutenberg-editor.png), [guest final](evidence/gutenberg-guest-final.png), [fallback](evidence/gutenberg-empty-fallback.png) |
| RM-2 Elementor | Entered basic HTML through Code mode, checked Visual mode, published and reopened the editor. Guest output preserved bold, italic, list items and link; protected heading absent. | [Editor](evidence/elementor-editor.png), [reloaded](evidence/elementor-reloaded.png), [guest](evidence/elementor-guest.png) |
| RM-3 CPT | Created a rule targeting one test page, added login condition after editor initialization, formatted and saved message. Reload retained message and rule settings. Discard restored saved text. Absolute link survived save/reload. Admin saw protected content; guest saw the message. | [Editor](evidence/cpt-editor-saved-visible.png), [guest final](evidence/cpt-guest-final.png), [authorized](evidence/cpt-authorized.png) |
| RM-4 Per-post | Enabled login-only restriction, italicized message, applied and published. Reopening retained formatting. Link insertion, modal submission and final page save all worked after stacking fix. | [Reopened](evidence/post-rule-reopened.png), [guest final](evidence/post-rule-guest-final.png) |
| RM-5 Safety and cleanup | Actual guest page retained safe formatting and removed script tags, event handlers and javascript URLs; no injected JavaScript executed. Twelve supplemental rendering/sanitization checks passed both with Pro loaded and skipped. Temporary pages/rule removed. | [Browser safety](evidence/browser-safety.txt), [core checks](evidence/rendering-checks-core.txt), [Pro checks](evidence/rendering-checks.txt), [cleanup](evidence/cleanup.txt) |

Screenshots were visually inspected. Final Edge and guest sessions reported no JavaScript errors. Production webpack build passed. Changed source files range from 172 to 803 lines, below the 3000-line limit. No phpcs or expensive lint suite was run.

## Issues found and fixed

- QA issue **38**: TinyMCE converted absolute links into paths relative to wp-admin. Disabled URL conversion and verified full href persistence in Gutenberg and CPT.
- QA issue **39**: CPT/per-post messages wrapped rich paragraphs inside another paragraph. Removed wrapper and verified the final DOM.
- QA issue **40**: TinyMCE link popup appeared below the WordPress restriction modal. Scoped popup/backdrop stacking and retested URL entry, confirmation, save, reload and guest rendering.

All three are closed in `qa/issues/`.

## Environment notes

Chrome for Testing 149 displayed a blank Gutenberg canvas with WordPress `block-editor.js` reporting `contentDocument` as null for its blob iframe. This occurred on repeated page loads before block editing. Continued Gutenberg/per-post tests through agent-browser with installed Edge 153, where the canvas and controls worked normally and no JS errors were reported. Elementor/CPT/guest tests used Chrome for Testing.

WP-CLI used the existing Local MySQL socket via PHP's `mysqli.default_socket`, always with `--allow-root`. Existing Elementor PHP deprecation and WordPress AI-provider notices appeared during CLI bootstrap and did not prevent checks.

Temporary page IDs: 28477, 28478, 28479, 28480, 28512; temporary auto-drafts: 28475, 28476. All were removed after verification. Other work in progress in the workspace was preserved.
