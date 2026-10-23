# Elementor authoring verification

Date: 2026-10-24, Asia/Dhaka. Active QA task 2, plan `../plan.md`.

Target: `http://localhost:10013/wp-admin/post.php?post=20008&action=elementor`, disposable page **QA unified author Elementor 20261024**. Administrator ID 1 (`admin`). Subscription/order IDs: N/A for this authoring check. Used isolated agent-browser sessions `unified-author-elementor`, `unified-author-elementor-reload`, and `unified-author-elementor-final`; mutations were made through actual editor controls and Publish. DOM/model inspection was read-only verification.

Passed authoring checks:

- Enable Restriction reveals the unified flow. Group order is Who can access it, Match Logic, Always Show To Admins, How denied access is handled. Conditions appear in the requested order, with login before the separator and Plan Feature after spend. Restriction Type/visibility controls are absent.
- Spend `0`, AJAX role Subscriber, and subscription status Active each automatically check and disable Logged-in users. Clicking the locked role-based login switch leaves it checked. Clearing the final spend/role condition unlocks login, keeps it checked, and permits the user to turn it off.
- Match ANY and administrator bypass off remain selected after Publish/reload. The editor preview continues to show editable content, as expected for the builder; visitor access is covered by the root browser/runtime matrix.
- The actual feature AJAX picker loads Team Seats. Selecting it reveals How To Check It. Presence mode hides the amount; minimum-total reveals the amount; custom comparison reveals amount, comparator, and aggregation before Match Logic.
- The final user-requested message control is **Use custom message**, native key `arraysubs_custom_message_enabled`, default off. On reveals the WYSIWYG editor; off hides it and retains its saved custom text. Toggling off/on restores the same text. Rich text entered through the editor's Code tab and displayed in Visual mode survived Publish/reload as `<p>Elementor QA <strong>custom message retained</strong>.</p>`.
- True reload preserves Active status, automatic locked login, ANY, administrator off, custom-message on, custom HTML, selected Team Seats, feature amount `2`, and custom comparison. The saved feature label and model agree after AJAX backfill.
- Entity-safe comparator `gte` displays At least (>=) after Publish/reload. Selecting `lt`, publishing and reopening displays Less than (<), with model value `lt`. The new control's default metadata is `gte`. Separate PHP mapping checks passed 27 operator/amount/aggregation assertions.
- ArraySubs checked switches and helper links compute `rgb(56, 88, 233)`, matching the current body's WordPress `--wp-admin-theme-color: #3858e9`. WordPress's native scheme variables are loaded, and styles are scoped to ArraySubs controls/dropdowns. Other Elementor controls retain their native appearance. No user profile or global setting was changed.
- Final browser error log is empty. PHP/JS syntax and diff whitespace checks pass. Source line counts: Hooks.php 791, editor.js 241, editor.css 38, LoginSwitcher.php 20.

Issues found, fixed, and closed during this real authoring pass:

- QA issue 95: custom login control appeared as a checkbox. Added the native switcher CSS class; actual switch shape, disabled state, and later unlock verified after reload.
- QA issue 96: HTML-encoded default comparator caused a blank selection. Introduced entity-safe operator keys with shared-symbol mapping; real saved/reloaded comparison checks passed.
- QA issue 97: Elementor's own accent leaked into ArraySubs controls. Loaded native WordPress palette definitions and scoped ArraySubs styles; computed colors and screenshot verified.
- QA issue 98: saved feature AJAX backfill selected every returned option and displayed the wrong label. Limited backfill to requested still-selected IDs; Team Seats now remains correct after reload.

Evidence:

- `elementor-final-reloaded.png`, `.txt`, and `.json`: final grouped layout, retained rich text, Team Seats, login lock, saved ANY/admin/custom state and computed accent.
- `elementor-custom-off.png`, `.txt`, and `.json`: final-polarity custom switch off hides the editor while retaining the same HTML.
- `elementor-comparison-lt-reloaded.png` and `.json`: alternate comparator persisted through real Publish/reload.
- `elementor-spend-zero.png` and `.txt`, `elementor-login-unlocked.png` and `.json`, `elementor-role-lock.json`: spend boundary, clear/unlock, and role lock observations.
- `elementor-feature-comparison-before.*`, `elementor-feature-label-before.*`, `elementor-accent-before.json`: original defects, retained as evidence.
- `elementor-errors.txt`: empty final error log.

Limits: browser interaction specifically exercised spend, role, status, and plan-feature authoring. Product/variation/purchased-product frontend access behavior belongs to the shared runtime matrix and root browser pass. Earlier screenshots named `elementor-default-*` predate the user's final polarity correction; use the final `elementor-custom-off` and `elementor-final-reloaded` evidence for the delivered behavior.

Fixture page 20008 remains for the root agent's cleanup. No global settings, plugins, passwords, or other pages were changed by these authoring sessions.
