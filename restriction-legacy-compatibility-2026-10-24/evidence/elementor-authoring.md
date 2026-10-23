# Elementor legacy preservation and explicit conversion

Active QA task 2, 2026-10-24 (Asia/Dhaka), plan `../plan.md`. Disposable author page 20084: `http://localhost:10013/wp-admin/post.php?post=20084&action=elementor`. Administrator ID 1, login `admin`, administrator role. Subscription/order IDs: N/A. Browser sessions: `legacy-elementor` and `legacy-elementor-final`.

The fixture began with original unversioned `arraysubs_mode=restrict`, login-only enabled, administrator bypass off, deliberately failing product/role conditions joined with ANY, and an implicit custom rich-text message with no message-enable flag.

Passed real browser authoring:

1. Opening the Container's ArraySubs controls showed the original-rule notice and an accurate login-only summary. Current condition controls and automatic login locking were hidden. Publish remained disabled, so opening the controls created no dirty edit or silent conversion.
2. Added an unrelated Container CSS class through the actual UI, then Publish/reload. All eight original saved ArraySubs fields retained their exact values. The implicit custom-message flag remained absent. A version-1 format marker recorded the original contract without changing it. The original-rule notice remained visible.
3. Clicked **Convert to current rules**. The local draft became version 2, required login, cleared the product/role conditions that had been ignored by the original login-only override, and kept custom messaging enabled with the same rich HTML. Login remained editable because no member conditions were selected. The saved source was still version 1 before Publish, confirming this was only a draft conversion.
4. Published and reopened the actual editor. Saved version 2, login on, empty dormant product/role conditions, custom-message flag on, visibly checked custom-message switch, and original rich-text message all persisted. The original-rule notice was hidden and the unified grouped controls appeared.
5. Final native browser error log was empty. Node behavior checks passed 23 assertions for new/legacy/current/transitional format identification, explicit empty message flags, imported numeric marker normalization, and conversion starting values. PHP/JS syntax and diff whitespace checks passed. Every modified source file remains below 3000 lines.

QA issue 101 was found and fixed during conversion testing: Elementor's standard custom-message switch did not refresh after a programmatic local draft conversion, even though its model and editor were enabled. The conversion now sends Elementor's native external-change notification. A fresh-session real conversion and Publish/reload verified matching model, switch, and editor states. Issue 101 is closed and released.

The separate shared runtime/browser passes cover original guest/logged-in visibility, empty and shortcode fallbacks, old login overrides, administrator bypass, implicit custom messages, positive/inverse message flags, transitional formats, and ordinary/imported version-2 markers. The actual-render leak found in QA issue 100 was fixed by overlaying original saved fields for every format before evaluating access; parsed dynamic values still take precedence. This authoring pass did not reseed the page with guest-only visibility, because that source format was already covered by the shared actual-container and storefront tests.

Evidence:

- `elementor-legacy-open.png` and `.txt`: read-only original-rule notice and login-only summary.
- `elementor-before-unrelated-save.json` and `elementor-after-unrelated-save.json`: exact preservation of all eight original fields and the absent implicit-message flag.
- `elementor-converted-draft.png` and `.json`: reviewed version-2 draft, enabled custom message, cleared dormant conditions, and still-version-1 saved source.
- `elementor-converted-reloaded.png`, `.txt`, and `.json`: actual Publish/reload persistence of the conversion.
- `elementor-converted-draft-before-refresh.json`: original issue-101 mismatch, retained as evidence.
- `elementor-errors.txt`: empty final browser error log.

The fixture remains for root-agent cleanup. No passwords, global settings, plugin activation states, or other pages were changed by these authoring sessions.
