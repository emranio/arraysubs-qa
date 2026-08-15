---
id: 3
title: 'stage-5: Initial Stripe SCA checkout sends renewal-verification email'
status: closed
priority: high
created: 2026-08-15T10:01:40.920514985+02:00
updated: 2026-08-15T12:19:18.48264898+02:00
started: 2026-08-15T11:40:33.354114507+02:00
completed: 2026-08-15T12:19:18.482648338+02:00
tags:
    - stage-05
    - stripe
    - sca
    - email
    - checkout
class: standard
---

QA source:
- Progress task: qa/progress task #3, stage-5.
- Lifecycle QA task: qa/subscription-lifecycle-test/kanban task #133.
- Primary plan: qa/stages/05-checkout/11-stripe-test-card-flow.md, Sub-Tasks 11.5 and 11.6.
- Contract cross-check: qa/subscription-lifecycle-test/kanban/tasks/030-classic-checkout-with-stripe-sca-card-3ds-at.md assigns the Renewal Requires Verification email to the later off-session renewal, not the initial on-session checkout.

Affected records:
- Subscription #27403; parent order #27387; product #197 Basic Monthly.
- WordPress customer #466, stripe-mig-sca-20260815-hawktwig@example.test, role customer.

URL and browser context:
- https://mirror-help.arrayhash.com/checkout/
- https://mirror-help.arrayhash.com/checkout/order-received/27387/
- Isolated agent-browser session stripe-mig-qa-sca, Chrome via agent-browser 0.27.3.

Reproduction:
1. Start a fresh Stripe subscription checkout and use the SCA test card ending 3184.
2. Submit once and wait for payment_intent.requires_action / the in-page 3DS2 challenge.
3. Before completing the challenge, inspect Mailpit and the pending subscription notes.
4. Click COMPLETE exactly once and verify that the same order/subscription becomes paid/active.

Expected:
The on-session 3DS challenge is handled by the visible checkout modal. Initial checkout must not be described to the customer or timeline as a subscription renewal. The Renewal Requires Verification email/note belongs to a later off-session renewal that cannot authenticate interactively.

Actual:
At 2026-08-15 07:42:51 UTC, while initial order #27387 and new subscription #27403 were still pending, ArraySubs sent Mailpit message 4wztrCJbIlTByQVpv9MSXt with subject "[mirror-help.arrayhash.com] Verify your subscription renewal #27403". It also created note #27407, "[Gateway: stripe] Renewal payment requires customer authentication. Verification email queued.", and note #27408, "Email sent: [ArraySubs] Renewal Requires Verification."

Concrete proof:
- Stripe webhook #1206 was payment_intent.requires_action at 07:42:51 UTC.
- The challenge was then completed once; webhook #1208 charge.succeeded and #1210 payment_intent.succeeded followed.
- The same order #27387 became paid/processing and the same subscription #27403 became active; no duplicate order/subscription occurred.
- Evidence: qa/artifacts/payment-migration-regression-20260815/stripe/report.md and screenshots/04-sca-*.png.

Scope and counterexamples:
- The generic 4242 subscription and mixed-cart flows did not emit a verification email because no SCA was required.
- Payment correctness, gateway binding, activation, and future scheduling all passed.
- This finding is customer-facing trigger/wording accuracy for initial SCA checkout; it is separate from the longstanding duplicate webhook-note issue #1.
- Whether this behavior predates the migration is not established by this run.

[[2026-08-15]] Sat 10:08
Evidence capture is complete. The disposable SCA order/subscription/user will be removed during exact QA teardown; the issue remains open with Mailpit ID, note IDs/content, webhook chain, screenshots, and report retained.

[[2026-08-15]] Sat 10:26
Final teardown note: the documented disposable users, orders, and subscriptions have now been removed after evidence capture. The issue remains open; retained reports, screenshots, webhook rows, Mailpit IDs, metadata snapshots, and implementation pointers are the reproduction evidence.

[[2026-08-15]] Sat 11:42
Engineering root cause and fix plan:
- Both Core and Pro StripeDelegate funnel every payment_intent.requires_action event through storeRequiresActionContext(). That helper assumes its order is a renewal, writes renewal verification context, logs renewal wording, and fires arraysubs_renewal_requires_verification. Initial on-session checkout orders do not carry the canonical _is_renewal_order=yes marker, so the event can be classified safely without guessing from status or timing.
- Fix plan: branch requires-action persistence on the canonical renewal-order marker. For an initial checkout, retain the Stripe intent metadata needed for reconciliation and write one neutral, idempotent checkout-authentication note, but do not create renewal payment-action URL metadata and do not fire the renewal verification email hook. For a true renewal, preserve the current context metadata, note, hook, and email behavior unchanged. Mirror the implementation in Core and Pro.
- Verification plan: run an isolated initial-vs-renewal WP-CLI probe with the email hook replaced by a counter, then perform a fresh real browser checkout with the 3184 card. Pause at the actual 3DS challenge and prove Mailpit has no renewal-verification message and the pending subscription has no renewal note/context; complete the challenge once and prove the same order/subscription becomes paid/active with correct Stripe binding, webhooks, actions, mail, browser network, and no PHP errors. No lint or PHPCS per workspace instructions.



Final fix and browser verification (2026-08-15):
- Core and dormant Pro fallback now classify renewal verification only when both canonical markers are present: _is_renewal_order=yes and the committed Stripe charge-attempt gateway. Initial and customer-paid on-session authentication retain Stripe intent metadata but do not receive renewal payment-action metadata or fire the renewal-verification hook. The neutral checkout-authentication note is durably de-duplicated.
- Fresh browser test used customer #470 (stripe-sca-fix-20260815@example.test), parent order #27598, and subscription #27614 with Stripe SCA Visa ending 3184. At the visible 3DS challenge, order/subscription remained pending; internal webhook #1221 was payment_intent.requires_action; Mailpit remained exactly at baseline 3N6JcM0eoDrvQYq84uTBap; the order and subscription had no renewal action URL/intent metadata; and the timeline contained one neutral checkout-authentication note with no Renewal wording.
- The challenge was completed exactly once. The same order #27598 became processing/paid and the same subscription #27614 became active; no duplicate records were created. Webhooks #1223 charge.succeeded and #1225 payment_intent.succeeded followed. Stripe cus_/pm_/ch_ bindings, Visa 3184, completed payments=1, and the exact reminder/invoice/process actions are present. Exactly four normal signup messages arrived and no Verify your subscription renewal message arrived.
- Browser receipt and challenge evidence: qa/artifacts/payment-bug-fixes-20260815/issue-003/sca-challenge-expanded.png and order-received.png. Browser errors are empty; only the known WooCommerce dependency warning is in console; debug.log stayed at 3557 lines.
- A controlled later off-session renewal exposed a separate webhook/lifecycle defect and was recorded independently as issue #4; it does not invalidate the confirmed initial-checkout trigger fix.
Result: FIXED and real-browser verified.
