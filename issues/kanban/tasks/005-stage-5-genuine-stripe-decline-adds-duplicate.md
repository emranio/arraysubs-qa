---
id: 5
title: 'stage-5: genuine Stripe decline adds duplicate failure notes'
status: closed
priority: high
created: 2026-08-15T13:50:07.732420552+02:00
updated: 2026-08-15T18:10:45.647428418+02:00
started: 2026-08-15T13:50:11.722883251+02:00
completed: 2026-08-15T18:10:45.647427587+02:00
tags:
    - stage-05
    - stripe
    - renewal
    - subscription-notes
    - payment-migration
class: standard
---

QA source:
- Progress task: qa/progress task #3, stage-5.
- QA plan: qa/stages/05-checkout/11-stripe-test-card-flow.md.
- Found during the post-fix genuine-decline negative control for issue #4.

Affected records:
- Subscription #27614; renewal order #27707; Stripe PaymentIntent pi_3U4fw6JG5OzSNVs20pNP8b7V; failed charge ch_3U4fw6JG5OzSNVs203172rL3.
- WordPress customer #470, stripe-sca-fix-20260815@example.test, role customer.

Route and browser context:
- https://mirror-help.arrayhash.com/my-account/view-subscription/27614/
- Browser session qa-fix-stripe-sca.

Reproduction:
1. Attach Stripe sandbox decline-after-attaching method ending 0341 to the disposable customer.
2. Run the canonical automatic renewal pipeline for subscription #27614.
3. Wait for payment_intent.payment_failed webhook #1258.
4. Open the customer subscription detail and inspect its notes.

Expected:
- The genuine card_declined renewal remains a real failure, sends its failure email, and creates one semantic payment-failed subscription note.

Actual:
- Payment behavior is correct, but two byte-identical renewal_payment_failed notes were created for order #27707: note IDs #27713 and #27716 at 2026-08-15 11:39:40 UTC.
- The same synchronous + webhook replay also delivered two customer failure emails and two ArraySubs admin failure emails for the one provider attempt.
- The same race delivered WooCommerce's generic admin `Order #27707 has failed` email twice as well.

Proof:
- Stripe PI status requires_payment_method, last_payment_error.code card_declined, decline_code generic_decline; failed charge count exactly one.
- Local order #27707 failed, retry counter became 1, and Mailpit ID 1pLSUsyYOVvi6wjaldOH8Z contains the expected payment-failed email.
- Browser customer portal displayed the two identical entries; DB query confirms IDs #27713/#27716, identical content, event_type renewal_payment_failed, and no semantic effect key.
- Mailpit confirms the duplicate pairs: customer IDs 1pIdwTEFMeeUqE8DAkcw5i and 3x0fgyjVt5VV8bWtnL8pNx; ArraySubs admin IDs 16QHMDr4zJ9JVLYgN8TYJT and 1pLSUsyYOVvi6wjaldOH8Z; WooCommerce admin IDs 43ZIX1zoaXtkiAWNm8h9rh and 0FpiuAj9x3g2Sy57dRn3yG.
- Screenshot context: qa/artifacts/payment-bug-fixes-20260815/issue-004/cancellation-action-before.png.

Known scope and counterexamples:
- This is duplicate audit/timeline output, not a duplicate charge: provider charge count is exactly one.
- Authentication-required renewals remain pending and send a verification email rather than this genuine-failure path.
- Closed issue #1 covered duplicate success-confirmation notes; this is the analogous genuine-failure path and is separately reproducible.

Fix plan (2026-08-15):
- Carry an additive, exact provider-attempt reference on `arraysubs_gateway_payment_failed` from both the synchronous renewal result and the signed Stripe webhook. Existing four-argument listeners remain compatible.
- Serialize the customer-visible failure-note check and insert under the shared subscription advisory lock, keyed by gateway + subscription + order + provider attempt + normalized error. A new retry PaymentIntent on the same reused order must receive a new note.
- Add a separate per-effect email claim so customer and admin failure emails are each delivered once for the exact provider attempt, without suppressing the underlying lifecycle hook.
- Gate WooCommerce's generic Stripe renewal failed-order admin recipient with its own exact-attempt claim so concurrent pending-to-failed transitions cannot send it twice.
- Verify same-attempt replay, a distinct second attempt, and a fresh real Stripe decline with Mailpit and browser evidence.

Adversarial delivery review and amended plan:
- The first email-ledger implementation trusted the first nonempty local PI pointer for WooCommerce's failed-order mail, marked it `sent` before `wp_mail()` returned, retained a same-request bypass indefinitely, and held the shared gateway-meta lock across template rendering/SMTP. Those paths could suppress the wrong retry, permanently lose a failed delivery, duplicate a second same-request trigger, or block webhook metadata work.
- Replace that implementation with an exact legacy-attempt resolver plus a short pending claim token. Claim/finalize/release mutations occur under the subscription lock; actual mail rendering and transport occur outside it. Finalize only from the exact WooCommerce email object's real `woocommerce_email_sent` result, release failed/skipped/disabled delivery, expire abandoned claims, and clear the request-local bypass after the matching callback.
- ArraySubs customer/admin effects must independently observe their exact WC email object's real transport result; disabled, recipientless, exception, and failed-mail paths must release rather than claim `sent`, and a customer-mail failure must not prevent the admin effect.


Resolution verification — final:
- Failure-note creation and each mail side effect are now claimed atomically for the exact provider attempt while delivery itself occurs outside the subscription metadata lock.
- The shared payment logging lock wrapper in both core and Pro was corrected to preserve and return the callback result while satisfying the GatewayMetaStore lock contract; failed/skipped transport can therefore release its claim instead of being falsely finalized.
- A fresh real Stripe generic-decline renewal remained failed with one provider attempt and one failed charge, so the genuine failure lifecycle was not suppressed.
- The customer portal displayed exactly one semantic renewal-payment-failed note for that attempt.
- Mailpit captured exactly the three expected failure messages: one customer ArraySubs message, one ArraySubs admin message, and one WooCommerce admin failed-order message. Same-attempt synchronous/webhook replay added zero messages and zero notes.
- A distinct later provider attempt remained eligible for its own notification effects, proving dedupe is attempt-scoped rather than order-wide.
- Browser proof: qa/artifacts/payment-bug-fixes-20260815/core-only-final/stripe-decline-deduped-customer-portal.png.
- Closed after live provider, Mailpit, database/meta, webhook replay, and customer-browser verification.
