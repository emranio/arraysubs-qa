---
id: 57
title: Stripe declines are always classified as hard card_declined, so retries never run
status: closed
priority: high
created: 2026-10-19T11:25:49.477920511+02:00
updated: 2026-10-20T00:35:28.103880281+02:00
started: 2026-10-19T20:18:45.388577485+02:00
completed: 2026-10-20T00:35:28.10387948+02:00
tags:
    - stripe
    - retry
    - dunning
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: S03-A / S04-A (board tasks #9/#10 in qa/stripe-dev-support-2026-10-18/kanban), phase 2, 2026-10-19.
Plan markdown path: qa/stripe-dev-support-2026-10-18/plan.md.
Affected subscription IDs and order IDs: subscription 68439 (trial product 68435, card 4000 0000 0000 0341), initial trial order 68438, failed renewal order 68473 (pi_3USCo3JG5OzSNVs20MNA9LRC). Scheduled action 100916 (arraysubs_process_renewal) run explicitly at 2026-10-19 09:24:34Z.
Affected WordPress user/customer: ID 579, sqa-p2-decline / sqa-p2-decline@example.com, role customer.
Exact route: WP-CLI `wp action-scheduler action run 100916 --allow-root` on https://mirror-help.arrayhash.com (fixture action only); admin view https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/68439. Settings: payment_retry enabled, interval_hours [24,24,24], stop_on_hard_decline true. Core 4791832 + Pro edaa401, official WooCommerce Stripe Gateway 10.9.0 test mode.
Reproduction: buy the 1-day-trial product with card 0341 (attaches, charges fail); end the trial with the developer API, bring the due date forward with setNextPaymentDate(time()+90), let the invoice run, run the process_renewal action.
Expected: Stripe's soft decline (decline_code generic_decline) is classified generic_decline, which is not a hard decline, so automatic retry 1 of 3 is scheduled 24 h later. An insufficient_funds decline should likewise be classified insufficient_funds and retried.
Actual: Stripe returned code=card_declined, decline_code=generic_decline. ArraySubs stored _last_payment_failure_category=card_declined and wrote "No automatic retry was scheduled because this failure will not resolve on its own: the card was declined by the issuer." No retry action exists; _payment_retry_attempts is empty.
Cause (code reading): StripeRenewalPaymentTrait.php:325 passes only `$error_data['code']` to buildFailure(); the Stripe `decline_code` is never used. Stripe sets code=card_declined for every issuer decline (generic_decline, insufficient_funds, lost_card, do_not_honor ...), and arraysubs_classify_payment_failure_reason() maps card_declined to the hard category card_declined (gateway-helpers.php:520, :371). So with the default stop_on_hard_decline=true the Stripe retry ladder never runs for any card decline, and the customer email/notes lose the specific reason (e.g. insufficient funds).
Proof: snapshots/sub-68439-s03-after-decline.json (recovery.failure_category card_declined, retry_attempts 0, no pending process_renewal action); subscription notes listed in the phase 2 handoff; Stripe PI last_payment_error above.
Scope notes / counterexamples: the documented classifier itself handles generic_decline/insufficient_funds correctly when given the decline_code. Only the Stripe adapter's input is wrong. Authentication-required failures take a separate requires_action path and are unaffected by this. With stop_on_hard_decline=false the ladder would run (being verified in S04).
Repro video: N/A.

Deep re-verification 2026-10-19: CONFIRMED WITH CORRECTIONS (high, high confidence). Fresh sub 68665 / pi_3USEJEJG5OzSNVs21r9uU7nr: code card_declined, decline_code generic_decline, advice_code try_again_later -> stored card_declined, no retry. Corrections: 'always' overstated - processing_error is retried, expired_card/incorrect_cvc keep their own hard categories; classifier returns the direct code map before the message fallback; webhook handler (StripeWebhookEventsTrait.php:86) also reads only code; unmapped decline codes (lost_card, stolen_card, card_velocity_exceeded...) need a mapping; customer email loses the reason, notes keep the raw message. Live meta on 68439/68665 was later wiped by #64 - rely on saved evidence. Full report: qa/stripe-issues-56-61-deep-verification-2026-10-19.md

Closed 2026-10-19 (UTC) after fix + live verification. Changes are in the working trees and NOT committed (user request). Full record: qa/stripe-issues-56-64-fix-log.md.

Fix 2026-10-19 (uncommitted; log qa/stripe-issues-56-64-fix-log.md): the Stripe delegate classifies failures with `stripeFailureCode()` (`decline_code` before `code`; unknown decline codes -> `generic_decline`; `advice_code` `do_not_try_again` / `confirm_card_data` keep or force a hard reason, `try_again_later` softens a hard one). The classifier map covers Stripe's decline codes (`do_not_honor`, velocity codes soft; lost/stolen/fraudulent/pickup hard) and pre-charge verification codes (`renewal_payment_method_binding_invalid` -> `missing_payment_method`, hard). Files: core `functions/gateway-helpers.php`, `StripeApiTrait.php`, `StripeRenewalPaymentTrait.php`, `StripeWebhookEventsTrait.php`.
Verified live 2026-10-19: T2 (fixture 593, sub 69202, renewal 69222: `card_declined` / `generic_decline` / `try_again_later` -> stored `generic_decline` (soft), "Scheduled retry 1 of 3" action 101503, email "Reason: the card was declined."); T9 (68788 / renewal 69261: binding invalid -> `missing_payment_method`, no retry). WP-CLI probe: 18 classifier cases as designed.
Not covered here: asynchronous `payment_intent.processing` -> failed (SEPA) is still not classified or retried.
Review round 2: Woo Stripe answering null (rejected API key) is now a temporary error (`stripe_empty_response` → `processing_error`, retried) instead of "no payment method"; a PaymentMethod Stripe reports as `resource_missing` is `missing_payment_method` (hard). Probe evidence `qa/stripe-dev-support-2026-10-18/evidence/fix/fix-r2-probes-before-live.json`.
Review round 3: when a retry cannot read the PaymentIntent already on the order (timeout, 5xx, rejected key), it now tries again every hour for up to a day instead of stopping; a payment method that exists only in the other Stripe mode/account stays a temporary error. Probes `evidence/fix/fix-r3-probes.json`. Not covered: asynchronous (SEPA) failures, filed as #67.
Review round 4: `resource_missing` stays soft only when Stripe says the object exists in the other mode ("similar object exists"); otherwise it is a missing payment method (account migration without the customer).
