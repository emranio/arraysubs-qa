---
id: 1
title: 'stage-5: Stripe webhooks add duplicate payment-confirmed subscription notes'
status: closed
priority: high
created: 2026-08-15T09:51:11.590726136+02:00
updated: 2026-08-15T11:16:01.96980353+02:00
started: 2026-08-15T10:45:05.863671961+02:00
completed: 2026-08-15T11:16:01.969802628+02:00
tags:
    - stage-05
    - stripe
    - webhook
    - subscription-notes
    - payment-migration
class: standard
---

QA source:
- Progress task: qa/progress task #3, stage-5.
- Lifecycle QA task: qa/subscription-lifecycle-test/kanban task #133.
- Plan: qa/stages/05-checkout/11-stripe-test-card-flow.md and qa/stages/05-checkout/14-mixed-cart-and-block-checkout.md.

Affected records:
- Subscription #27296, parent order #27280, customer #463, stripe-mig-sub-20260815-hawktwig@example.test, role customer.
- Subscription #27362, parent order #27346, customer #465, stripe-mig-mixed-20260815-hawktwig@example.test, role customer.
- Subscription #27403, parent order #27387, customer #466, stripe-mig-sca-20260815-hawktwig@example.test, role customer.
- Regular-only order #27315 / customer #464 is the no-subscription counterexample.

URL and browser context:
- https://mirror-help.arrayhash.com/checkout/
- Order-received routes for orders #27280, #27346, and #27387.
- Isolated agent-browser customer sessions: stripe-mig-qa-sub, stripe-mig-qa-mixed, stripe-mig-qa-sca.

Reproduction:
1. Purchase a paid subscription with Stripe test card 4242, or complete 3DS with the card ending 3184.
2. Wait for both charge.succeeded and payment_intent.succeeded.
3. Inspect the ArraySubs subscription notes.
4. Filter for "Stripe payment confirmed via official webhook for order".

Expected:
One de-duplicated official-webhook confirmation note per successful initial payment. A separate intentionally distinct normalized payment-success timeline note is acceptable.

Actual:
Each tested subscription contains two identical official-webhook confirmation notes:
- #27296: notes #27300 and #27301 for order #27280.
- #27362: notes #27366 and #27367 for order #27346.
- #27403: notes #27409 and #27410 for order #27387.
Each also has one distinct Payment successful timeline note, which is not the duplicate.

Proof:
- Stripe webhook pairs: internal #1152/#1154, #1183/#1185, and SCA #1208/#1210 after requires_action #1206.
- Browser checkouts succeeded; all three orders are paid/processing and all three subscriptions active.
- Evidence: qa/artifacts/payment-migration-regression-20260815/stripe/report.md and stripe/screenshots/.
- Reproduced independently with subscription-only, mixed-cart, and SCA paths.

Scope/counterexample:
Payment, activation, gateway binding, and scheduling are correct. Impact is duplicate history/audit noise. Regular-only Stripe order #27315 created no subscription and no applicable notes. Concurrent Paddle events were separated by gateway and fixture IDs.

[[2026-08-15]] Sat 09:55
Historical scope check (2026-08-15): a read-only grouped query found the same identical-note duplication on many pre-migration Stripe subscriptions, beginning at least with subscription #1436 on 2026-05-31 (2 copies) and #4406 on 2026-06-03 (3 copies), plus numerous June-August renewals. Therefore this is a longstanding product/audit-history defect, not introduced by the ArraySubsPro-to-ArraySubs payment migration. The current three fixtures remain valid reproductions; payment correctness is unaffected.

[[2026-08-15]] Sat 10:08
Evidence capture is complete. The disposable reproduction subscriptions/orders/users will be removed during the exact QA teardown; the issue remains open and reproducible from the documented webhook pair, note IDs/content, historical grouped query, and retained screenshots/report.

[[2026-08-15]] Sat 10:26
Final teardown note: the documented disposable users, orders, and subscriptions have now been removed after evidence capture. The issue remains open; retained reports, screenshots, webhook rows, Mailpit IDs, metadata snapshots, and implementation pointers are the reproduction evidence.

[[2026-08-15]] Sat 10:56
Verified current code with an isolated WP-CLI subscription probe: charge.succeeded followed by payment_intent.succeeded created exactly two identical confirmation notes (four total notes including normal creation/status notes), then the probe was fully deleted. Root cause: StripeDelegate normalizes both provider events to the same payment-success handler and unconditionally writes the same order-scoped note. Fix plan: keep both authenticated events, metadata capture, webhook history, and success hooks intact, but make payment_intent.succeeded the canonical confirmation-note event and treat charge.succeeded as the lower-level companion. Mirror the fallback Pro copy, run the focused pair-event probe, then complete a fresh real Stripe browser purchase and verify one note, bindings, webhooks, actions, and mail.

[[2026-08-15]] Sat 10:57
Plan refinement after cross-plugin trace: both Stripe success events are intentionally provisioned, and removing or short-circuiting charge.succeeded could weaken replay/context behavior. The fix will therefore preserve both raw event claims, metadata capture, and success-hook dispatches. Only the semantic note side effect will be made atomic and durable, keyed by gateway + subscription + order + canonical PaymentIntent, with exact-content fallback for pre-fix notes. The shared subscription lock will serialize check-and-insert; the same implementation will be mirrored in the dormant Pro fallback.


Final fix and verification (2026-08-15):
- Root cause: Stripe intentionally delivers both charge.succeeded and payment_intent.succeeded with different provider event IDs; both normalized to the same success handler and independently inserted the same order-scoped confirmation note.
- Fix: preserved both raw event claims, context capture, and success hooks, but serialized the semantic note side effect under the shared subscription advisory lock. The durable key is gateway + subscription + order/canonical PaymentIntent, stored on the note; exact-content fallback covers historical/unkeyed and interrupted-meta cases. Core and dormant Pro fallback copies are aligned. Pro additionally feature-detects the new public lock and safely acquires the identical legacy advisory lock with older supported core, avoiding an upgrade fatal.
- Focused runtime checks: charge -> PaymentIntent -> replay created one confirmation; reverse event order created one; a distinct second order created a second note; two concurrent WP-CLI processes created exactly one note. All probes were deleted.
- Real browser retest: customer #468 purchased Basic Monthly #197 with Stripe test Visa 4242. Order #27480 is processing and subscription #27494 is active. Internal Stripe webhooks #1216 charge.succeeded and #1218 payment_intent.succeeded both processed, while the subscription has exactly one official-webhook confirmation note (#27498), event gateway_payment_succeeded, with its effect key. The intentionally distinct payment-success note remains.
- Binding and lifecycle proof: Stripe cus_/pm_/ch_ identifiers are present and internally consistent; Visa 4242, completed payments 1; exact pending reminder/invoice/renewal actions exist. Mailpit captured the four expected initial-order/subscription messages and no extra message. Store checkout POST and receipt returned HTTP 200, browser page errors are empty, and debug.log gained zero lines.
- UI proof: qa/artifacts/payment-bug-fixes-20260815/issue-001/order-received.png and admin-subscription-detail.png visibly show the successful receipt, active binding, and one confirmation note.
Result: FIXED and browser-verified. Fixture cleanup is tracked in the final exact teardown after all three gateway fixes.
