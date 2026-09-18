# Default restriction message — verified 2026-09-18

Site: http://localhost:10003. Browser: agent-browser with Microsoft Edge; isolated administrator (user 1, admin) and guest sessions. Scope and schedule: [plan.md](plan.md). Subscription/order/customer IDs: N/A. Temporary page IDs: 28519–28532, all removed after testing.

## Result

Passed. Content Gate now begins its navigation and sections with Default Message, containing a rich-text editor, explanatory info alert, and Save Default Message button. Saving uses the existing admin-only settings endpoint and submits only `default_message`. Custom gate/rule messages win; blank or visually empty custom HTML uses `members_access.default_message`; an empty global message uses the built-in text. Login-only gates follow the same chain. An active per-post gate takes precedence over an overlapping CPT rule's message.

## Browser checks

- Created a global message with bold formatting and a full absolute link through the actual toolbar. Saved, saw success feedback, reloaded, and confirmed HTML and URL persistence.
- Observed Save button disabled with “Saving…” during the request. Verified 600px narrow layout with no horizontal overflow. First button and section were read back from DOM. Screenshots: `evidence/final-content-gate.png`, `default-message-narrow.png`, `default-message-saved.png`.
- Ran 14 frontend fixtures in each of four states: global A, global B, empty global, and authorized administrator. **56/56 browser page checks passed.** Raw browser DOM results in `evidence/matrix-{A,B,empty,authorized}.json`.
- Fixtures covered default/custom Gutenberg login-only and role gates, Elementor containers, restriction shortcodes, per-post full-page rules, CPT rules, URL message and 403 actions, comment restrictions, and overlapping per-post/CPT rules.
- Denied guests could not see protected content; comment restrictions correctly retained the ordinary page content. Authorized administrators saw protected content without restriction notices. URL 403 cases returned HTTP 403.
- Changing the global message changed existing blank-message gates without modifying custom-message gates. Clearing the global editor persisted as empty after reload and invoked the built-in fallback everywhere tested.
- Added an italic custom message using the CPT editor and saved it. Guest output contained the custom `<em>` message. Reloaded the editor, cleared it, saved, and verified the fallback returned. Evidence: `cpt-custom-ui-saved.png`, `cpt-custom-cleared.png`.
- Safe paragraphs, strong/emphasis, lists and links rendered as HTML; absolute link destinations were preserved. Screenshot and computed-style inspection confirmed theme typography (parent weight 300; strong weight 400 via browser bolder), italic text and list markers.
- Guest GET and POST calls to the settings endpoint both returned 401; unauthorized write had no effect.

## Supplemental verification

`check-fallbacks.php` passed 13 assertions for visually empty paragraphs, line breaks, nonbreaking spaces, comments, custom precedence, meaningful "0", HTML sanitization, login-only fallback, and empty global fallback. Updated the previous rich-message check's login fallback expectation to the new shared-default contract; all 12 existing assertions passed. Core-only execution passed. Pro was inspected for shared consumers; no Pro changes were needed.

Production webpack build passed. Targeted `git diff --check` passed. Every touched source file remains under 3,000 lines (largest: CommentRestriction.php, 1,080). No phpcs or dependency installation was run.

## Cleanup

All 14 temporary pages and only the `qa-dm-*` rules were removed. Original default message and require-login value restored. Compared the complete settings option with the original JSON snapshot: all original settings and rules preserved. Test sessions closed. Existing QA issues were preserved; no new unresolved defects remain from this scope.
