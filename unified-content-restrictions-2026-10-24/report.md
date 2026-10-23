# Unified content restrictions

Completed 2026-10-24, Asia/Dhaka, on the real local WordPress site at `http://localhost:10013`. Scope and execution board: [plan.md](plan.md), [board/kanban/](board/kanban/).

## Result

Per-post Access Restriction, Gutenberg Restricted Content, and Elementor content restrictions now use the same flow:

1. **Who can access it:** Logged-in users, separator, subscription status, active products/variations, purchased products, roles, lifetime spend, and Plan Feature with its conditional children.
2. **Match Logic:** ALL or ANY applies only to the selected member conditions.
3. **Always Show To Admins:** a separate switch; when enabled administrators bypass all conditions, otherwise they must qualify normally.
4. **How denied access is handled:** existing message/redirect behavior, with **Use custom message** everywhere. On shows the editor; off uses the shared default. Custom text survives switching off, and empty custom text also uses the default.

Any populated member condition forces login on and disables that switch. Login is checked before ALL/ANY, never as an alternative that could satisfy ANY. Clearing the final condition unlocks the login switch. Login alone permits any logged-in user. Restriction-type/visibility mode selectors were removed from these editors.

Core owns all implementation. The Pro feature module remains optional; no premium source or release version was changed.

## Verification

| Check | Result | Evidence |
| --- | --- | --- |
| Final shared runtime with Pro | 332/332 passed | [Log](evidence/runtime-pro-final.txt) |
| Final shared runtime without Pro | 332/332 passed | [Log](evidence/runtime-core-final.txt) |
| Three surfaces × four user contexts × nine storefront states | 216/216 passed | [Assertions](evidence/browser-pro-checks.json), [run](evidence/browser-pro-run.txt) |
| Core-only storefront matrix | 144/144 passed | [Assertions](evidence/browser-core-checks.json), [completed continuation](evidence/browser-core-resume.txt) |
| Real subscription Team Seats feature positive/negative boundaries | 18/18 passed | [Assertions](evidence/browser-feature-checks.json) |
| Final custom-message switch behavior after requested label/polarity correction, core and Pro | 36/36 passed | [Assertions](evidence/final-custom-polarity-checks.json) |
| Per-post and Gutenberg authoring | Passed real selections, typing, save/reload, default/custom retention, feature children, and login locking/unlocking | [Authoring report](react-authoring-report.md), [final per-post switch](evidence/react-custom-switch-post-on.png), [final block switch](evidence/react-custom-switch-block-on.png) |
| Elementor authoring | Passed native switch/picker interaction, Publish/reload, comparison operators, selected feature labels, admin toggle, custom HTML, WordPress colors | [Authoring report](evidence/elementor-authoring.md), [final reload](evidence/elementor-final-reloaded.png) |
| Per-post and standalone shortcode redirects | Guest reached configured destination | [Per-post URL](evidence/per-post-redirect-url.txt), [shortcode URL](evidence/shortcode-redirect-url.txt) |
| Final production editor bundles | Passed for per-post Gutenberg/classic editors and Restricted Content block | [Build](evidence/editor-build.txt) |
| Final full production build | Passed after concurrent Plans page source became available | [Build](evidence/full-build.txt) |
| PHP syntax, whitespace, file size | Passed; validated source files remain below 3000 lines | [Validation](evidence/source-validation.json), [line counts](evidence/line-counts.txt) |

The runtime matrix covers all four adapters, real active subscription/product access, feature presence/combined/single-plan/custom comparisons, zero spend, missing and malformed logic, disabled gates, admin bypass on/off, default/custom/empty HTML, and internal shop/comment adapters. Browser authoring used agent-browser snapshots/references and real keyboard input; browser frontend checks used isolated guest, customer, member, and admin sessions. API/runtime checks supplemented real browser verification.

The core-only browser run initially hit a browser reload timeout after its first 96 passing assertions. The remaining 48 passed after removing a redundant reload; the combined assertions contain 144 passes. No authorization assertion failed in that run. The initial Gutenberg author fixture omitted its save wrapper; it was recovered and saved through the real editor, and final reloads had no validation error.

## QA fixes

Existing `qa/issues/` records were preserved. Newly recorded issues 94–98 were fixed, browser-verified, and closed: guest shortcode redirects, Elementor login-switch styling, feature comparator defaults, WordPress accent colors, and saved AJAX feature labels. The final native comparator keys map to the shared operators, and selected feature labels remain correct after true reload.

## Build and cleanup

Final full production build passed at 03:28:56. A temporary missing `pages/SubscriptionPlans/index.jsx` during concurrent workspace work was resolved before that final build; unrelated source changes were preserved. The restriction editor bundles also passed their scoped production build, and final switch wording/behavior was browser-verified against them. Elementor serves its editor assets directly from source.

Disposable fixtures: eight published pages (including authoring and redirect pages), customer 439 without subscriptions, existing member 224, and existing administrator 1. No orders or payments were created. All disposable pages/customer were removed. Original plugin settings, active plugins (including Pro), and storefront visibility were restored: [restoration checks](evidence/restoration-check.txt). The latest saved-at timestamp was retained instead of rolling back concurrent workspace saves; it differs from the initial timestamp. Unrelated settings/source changes were preserved.

Three temporary WordPress authentication tokens were revoked; root and authoring browser sessions were closed; private authentication state, token records and backups were removed: [cleanup](evidence/cleanup.txt). Existing account passwords were unchanged. Existing Elementor/WordPress deprecation notices remain in logs; no uncaught errors remained in final authoring sessions. No phpcs, Composer install, remote deployment, push, or release was performed.
