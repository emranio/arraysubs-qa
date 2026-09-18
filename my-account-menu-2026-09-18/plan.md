# My Account menu browser QA

Scope confirmed by the user: deeply test the Orders-independent Subscriptions navigation fix with real editor reordering, visibility changes, saved configuration, and actual Pro activation/deactivation. This is a fresh QA cycle; no older lifecycle board or schedule is reused.

Target: `http://localhost:10003` (the workspace's running WordPress site, verified by WP-CLI). Admin login: existing local auto-login URL `/wp-admin/?localwp_auto_login=1`. Customer: existing user 525, `qa_download_settings_second_20260917`, role customer, via the actual Login as Customer UI. Subscription 28240 is active; related order N/A (`_parent_order_id = 0`). Pro starts inactive; core 2.0.4.1 includes the current menu fix. Only the local site is in scope.

Use isolated agent-browser sessions `menu-deep-admin`, `menu-deep-customer`, and `menu-deep-guest`. All test mutations must use the actual browser editor, WordPress Plugins screen, and Permalinks/WooCommerce settings screens. Read-only WP-CLI may capture or verify persisted state. No request-only MU filters, direct setting writes, mocked REST or simulated Pro hooks are substitutes for this cycle.

Preserve existing issues 36 and 37 and unrelated product changes. Capture the starting menu/master-toggle/Orders-endpoint/Pro state before mutation. Restore only this cycle's settings through the UI; do not overwrite unrelated settings or concurrent edits. Do not change subscription status, dates, billing, orders, license credentials or external services. If restoring an exact serialized menu shape requires a narrowly scoped recovery write, document it separately from browser verification.

## Same-day execution schedule

Execute these phases sequentially on 2026-09-18. No unattended/recurring automation is required.

| ID | Phase / expected behavior |
| --- | --- |
| MA-01 | Free baseline with an old disabled Subscriptions entry: menu, list and owned detail work |
| MA-02 | Activate the actual Pro plugin through Plugins; inspect saved editor configuration, menu and direct URLs |
| MA-03 | Enable Subscriptions in the editor, save/reload; verify list, detail and navigation links as the customer |
| MA-04 | Reorder Subscriptions and Orders using real editor controls; save/reload; verify customer menu order and destination |
| MA-05 | Disable Orders while Subscriptions remains enabled; verify Subscriptions and owned detail remain usable |
| MA-06 | Disable Subscriptions; save/reload; verify missing link and deliberate dashboard redirect for pretty/query URLs; restore and retest |
| MA-07 | Disable the master customization toggle while saved Subscriptions remains disabled; free defaults return; re-enable and verify saved layout/visibility reapplies |
| MA-08 | Deactivate Pro with disabled/reordered configuration saved; free menu/portal return. Reactivate Pro; saved configuration reapplies. Verify visible-state activation cycle too |
| MA-09 | With Pro inactive, blank WooCommerce's Orders endpoint through its settings UI; verify fallback insertion and portal. Restore the endpoint. Refresh Permalinks and recheck routes |
| MA-10 | Guest behavior and repeated reload checks: no private subscription data; configured menu/route behavior persists |
| MA-11 | Restore original menu/master/Orders endpoint/Pro state, verify persisted state and customer portal, close sessions, summarize evidence and issues |

For each phase, use snapshots and screenshots, inspect the screenshot before judging visibility/order, and record concrete expected versus observed outcomes in `progress.md`. On a reproducible failure, log a QA issue in `qa/issues/` immediately with this phase ID, day, plan path, IDs, routes, context, steps and proof. A related fix may be made within the user's authorized scope, then repeat affected browser checks. Do not mark failed or untested checks passed.

Evidence: `qa/my-account-menu-2026-09-18/screenshots/` and sanitized observations. Video is preferred for interactive failures when the installed recorder can encode it; the earlier run found ffmpeg unavailable, so preserve step screenshots if video is unavailable. Do not store auth cookies, credentials, license secrets or private headers in evidence.
