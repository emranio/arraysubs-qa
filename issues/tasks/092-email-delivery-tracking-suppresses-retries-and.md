---
id: 92
title: Email delivery tracking suppresses retries and admin links target obsolete routes
status: closed
priority: high
created: 2026-10-23T03:18:51.360373+06:00
updated: 2026-10-23T03:38:50.787243+06:00
tags:
    - bug
    - emails
    - delivery
parent: 91
class: standard
---

Active QA: task #91, 2026-10-23 immediate manual run; plan qa/email-boundaries-2026-10-23/plan.md. Parent task #91.
Affected subscriptions/orders/customer IDs: N/A at discovery; runtime verification will use only disposable manual-payment fixtures and intercepted mail. Browser/user context: authenticated administrator email links, customer/administrator email delivery from http://localhost:10013. No SMTP/external sending.
Reproduction: intercept wp_mail with false, invoke a renewal invoice or other deduped email trigger, then retry with true. EmailManager::trigger reports true merely because a registered email object was invoked, even if Woo email is disabled, recipient absent, subscription invalid, or transport false; callers stamp sent markers and suppress the retry. Source proof: EmailManager.php:1873-1886, renewal invoice 937-943, payment success 909-915, reminder 1999-2001, trial 2127-2128, pending cancellation 809-816. Expected: only actual successful delivery stamps the relevant marker; retry remains possible after failure.
Also administrator cancelled/pending-cancellation/payment-failed HTML and plain templates use admin.php?page=arraysubs#/subscriptions/{id}, which does not match MainAdmin menu arraysubs-mainadmin or Main.jsx /subscriptions/detail/:id; new-subscription admin links already use the correct route. Expected: View Subscription opens the existing Free admin details page.
Known scope: expiring-soon and exact gateway-failure delivery already have a nested-safe actual-result observer. Free email classes and customer account/order-pay destinations exist in core. Root separately owns BaseSubscriptionEmail state/currency and helper/data changes, and final browser verification.
Fix plan before changes (cowman-soho): reuse the established trigger_email_and_observe_delivery contract from EmailManager::trigger, retaining exact object/subscription/order frames and nested caller state restoration; do not alter BaseSubscriptionEmail/helper. Align all six obsolete admin email links with the registered core page/detail route. Add real WordPress regression checks for failed transport and retry, disabled/invalid recipients, unrelated/nested email effects, exact gateway failure behavior, and rendered HTML/plain admin links. All mail intercepted; own fixtures cleaned and settings/plugins preserved. Commit only own source in isolated core worktree; no build/draft/deploy, root merges and verifies browser before closure.

[[2026-10-23]] Fri 03:24
Implemented Core commit pending integration in /tmp/arraysubs-email-delivery: public manager trigger now reuses the existing exact nested-safe delivery observer, pending-cancellation delivery retries track successful recipients independently and undo clears their markers, all six obsolete admin template links target arraysubs-mainadmin#/subscriptions/detail/{id}. Real WP candidate regression passed 44/44 in Free and 44/44 with actual Pro; all 28 mail calls per pass intercepted before transport. Baseline fails at false transport incorrectly reported true. Evidence/scripts: qa/email-boundaries-2026-10-23/delivery-regression.php, delivery-before.json, delivery-candidate-free.json, delivery-candidate-pro.json. Saved settings and activation unchanged; own fixtures cleaned. Manager is 2214 lines, all six templates under 110 lines; diff whitespace check passes, no lint/PHPCS/build/draft/deploy. Root owns peer review, integration rerun and actual browser link verification before closure.

[[2026-10-23]] Fri 03:25
Source commit d373655 is ready for root integration. Shared helper delivery-result contract discussed with root; helper and Base remain root-owned. Candidate pass provenance is real /tmp worktree EmailManager loaded before plugin boot using WP-CLI after_wp_config_load, verified by Reflection; template location is request-filtered to the candidate files. No site source deployment occurred.

[[2026-10-23]] Fri 03:26
Added shared delivery contract in follow-up commit 1965cd1 (apply after d373655): private static reference to the manager initialized by normal core boot, public static sendSubscriptionEmail delegates to the existing trigger/observer. No second instance or extra hooks. Root will route the public helper to it, preserving template_vars. Final candidate regression passes 46/46 in both Free and Pro, including direct static false/success calls. After merging the root helper, run delivery-regression.php integrated-helper for two additional real helper-result checks. Pending final primary/browser integration verification; do not close yet.

[[2026-10-23]] Fri 03:33
Root merged d373655 and1965cd1 and confirmed primary Pro delivery-regression.php integrated-helper passes 48/48. Independently peer-reviewed root f519c25/fc170c7/b22b1fc context/preview/currency/helper/template changes with no actionable defect found. New primary Pro missing-records-regression.php passes 398/398 across 25 classes for deleted product/customer and absent recorded email, all mail intercepted. Boot-time CLI-only module-off inventory passes 70/70 with core25 renderable, Pro4 credit classes/email hooks absent, retention event dormant, no module-save action, no mail or fixtures. Settings/activation unchanged. Keep issue in-progress claimed pending root real-browser corrected admin-link proof.

[[2026-10-23]] Fri 03:38
Final acceptance confirmed by root: integrated primary delivery-regression.php integrated-helper passes 48/48 in both Free and Pro, including actual disposable fixture IDs and helper delivery results. Browser verification confirms all four admin View Subscription preview URLs use the safe core Subscriptions list and the Admin Payment Failed order preview uses the native WooCommerce Orders list; clicking the Admin Cancelled preview View Subscription link rendered the actual core All Subscriptions page. Commit b109151 supplies safe preview arguments while actual email fixture tests retain correct real-ID destinations. Native Free UI registered 25 core / 0 Pro emails, then restored Pro to 29 total. Browser exact state comparison reports all seven restoration checks true in qa/email-boundaries-2026-10-23/browser/state-comparison.json. Independent source peer review passed; missing-record regression 398/398 each Free and Pro, request-only module-off inventory 70/70. All mail intercepted, own fixtures cleaned, settings/activation restored; no existing lifecycle or payment mutation. Acceptance complete; closing #92 and releasing cowman-soho.
