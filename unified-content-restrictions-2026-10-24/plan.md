# Unified content restriction flow

Scope confirmed by the user: per-post Access Restriction, Elementor content restrictions, and Gutenberg Restricted Content must share the same grouped flow. The login requirement is independent of ALL/ANY condition logic. Administrators bypass only when enabled. Denial messages use the shared default unless custom messaging is selected and nonempty.

Execution schedule: 2026-10-24, Asia/Dhaka, tasks 1–4 in this session. Fresh board: `board/kanban/`. Preserve existing `qa/issues/` tasks and unrelated source changes.

Target: real local WordPress site http://localhost:10013, serving this checkout. Existing administrator 1 and active member 224; create a disposable customer without memberships and three disposable published pages. Authentication uses temporary WordPress cookies, without changing passwords. Browsers use isolated admin, guest, member, and nonmember sessions through agent-browser only.

1. Shared runtime matrix across per-post, Elementor, Gutenberg and shortcode: empty/login-only, guest rejection, forced login, ALL vs ANY, zero spend, feature selection/children, admin bypass on/off, message default/custom/empty, HTML and redirect behavior. Validate REST/config persistence and internal shop/comment message consumers.
2. Browser authoring on all three surfaces: same group order, condition pickers, automatic non-editable login, ability to turn login off after clearing all conditions, Match Logic, admin switch, message toggle and saved custom text. Save, reload, inspect screenshots, and exercise per-post redirect destination.
3. Browser storefront with guest, customer, member, admin: login alone, ANY failing every condition, ANY one passing, ALL one failing, admin bypass on/off, shared/default/custom/empty messages. Verify actual protected content visibility for all three surfaces.
4. Core-only runtime and browser spot checks; production build, PHP syntax, diff and file-size checks; restore original settings/plugins/visibility, remove disposable content/customer and temporary authentication tokens; record results/evidence. No release, push, Composer install or phpcs.

Record any new defect in `qa/issues/` with this plan/task/date, fixture identities, browser/URL, exact steps, expected/actual and evidence. Do not reuse an old execution schedule.
