---
id: 58
title: Authentication-required Stripe renewal cannot be completed after a 3DS checkout
status: closed
priority: medium
created: 2026-10-19T11:35:08.657000674+02:00
updated: 2026-10-20T00:35:28.105405387+02:00
started: 2026-10-19T20:51:40.565343911+02:00
completed: 2026-10-20T00:35:28.105404605+02:00
tags:
    - stripe
    - sca
    - recovery
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: S03-A (board task #9 in qa/stripe-dev-support-2026-10-18/kanban), phase 2, 2026-10-19.
Plan markdown path: qa/stripe-dev-support-2026-10-18/plan.md.
Affected subscription IDs and order IDs: subscription 68466; initial order 68465 (completed after 3DS, pi_3USCluJG5OzSNVs21VOUU9Sa); renewal order 68494 (pending, pi_3USCsOJG5OzSNVs21pOjRAHA requires_payment_method after off-session authentication_required). Actions 100929 (invoice), 100930 (process_renewal, run explicitly 09:29:03Z), 100934 (verification email).
Affected WordPress user/customer: ID 580, sqa-p2-auth / sqa-p2-auth@example.com, role customer. Stripe customer cus_VT94tp2XiMw6kX, bound pm_1USClqJG5OzSNVs2cNuszaPC (card 4000 0027 6000 3184).
Exact routes: Block checkout https://mirror-help.arrayhash.com/checkout/; verification link from the email "Verify your subscription renewal #68466" (Mailpit 3bgg6BwCvhhIrEuNjHuHNG) -> /checkout/order-pay/68494/?pay_for_order=true&key=...&wc-stripe-confirmation=1; plain "Pay" link in My Account -> /checkout/order-pay/68494/?pay_for_order=true&key=... Browser session sqa-auth (customer, logged in). Core 4791832 + Pro edaa401, WooCommerce Stripe Gateway 10.9.0 test mode.
Reproduction: buy SQA Daily Stripe Plan with 4000 0027 6000 3184 and complete the 3DS test challenge; bring the next payment forward (setNextPaymentDate), run the invoice and the process_renewal action; open the verification email link; try to pay.
Expected: the customer can complete authentication for the existing renewal PaymentIntent with the bound card (one collection), as the email promises.
Actual: (1) After the 3DS checkout no WooCommerce payment token exists for user 580 (wp_woocommerce_payment_tokens has none), although ArraySubs bound the PaymentMethod to the subscription. (2) The verification page shows only an empty Payment Element (no saved card), does not start confirmation of the existing PaymentIntent, and any submission with the card entered again returns "Use the saved Stripe payment method shown for this renewal verification." The plain Pay link gives the same refusal. There is no Retry Payment button (failure_category is empty for this path), and "Update payment method" goes to the Stripe portal whose changes are refused (issue #56). The renewal stays unpaid and the subscription will drift into grace.
Cause (code reading): StripeSubscriptionCompatibility::validateRenewalActionPaymentMethod() (arraysubs/src/Features/AutomaticPayments/Services/StripeSubscriptionCompatibility.php:762-784) requires a posted saved WC_Payment_Token whose token equals _gateway_payment_method_id; when the card was vaulted only at Stripe (no WC token), that condition can never be met.
Proof: screenshots s03-auth-3ds-buttons.png, s03-auth-verify-page-2.png, s03-auth-pay-3ds.png, s03-auth-plain-after-pay.png, s03-auth-portal-recovery.png; snapshots/sub-68466-s03-auth-after-failure.json; Stripe PI list for cus_VT94tp2XiMw6kX shows only the two PIs above (no duplicate collection).
Scope notes / counterexamples: non-3DS checkouts (customers 576/577/579) did get WC tokens 61-64. The initial 3DS order itself completed only after WooCommerce Stripe's deferred webhook (~4 minutes after the 3DS challenge); that delay is WooCommerce Stripe behaviour. No duplicate charge occurred.
Repro video: N/A.

Deep re-verification 2026-10-19: CONFIRMED WITH CORRECTIONS, narrowed (medium, high confidence). Normal 3DS checkouts keep a WC token and the verification link completes on the same PI with one charge (Block 586/68736, classic 587/68843). Dead end needs: first saved token + 3DS at signup + the post-3DS browser call never reaching the site (order finished only by WC Stripe's deferred webhook) + an auth-required renewal within WC Stripe's 24h empty payment-method cache; reproduced on fault-injected 590/68846. ArraySubs part: validateRenewalActionPaymentMethod requires a visible WC token although confirmation only needs the bound pm id. Code-only risk: stores with WC Stripe saved_cards=no would dead-end every auth renewal (would raise to high). Retest 68494 after 2026-10-20 09:22:23Z for the self-heal. Full report: qa/stripe-issues-56-61-deep-verification-2026-10-19.md

Closed 2026-10-19 (UTC) after fix + live verification. Changes are in the working trees and NOT committed (user request). Full record: qa/stripe-issues-56-64-fix-log.md.

Fix 2026-10-19 (uncommitted; log qa/stripe-issues-56-64-fix-log.md): the renewal verification page lists exactly the subscription's card: its WooCommerce token if present, else a token-free "arraysubs-bound" choice (signed-in order owner only); the new-card form and express buttons are hidden; the saved-card list is forced on for that request. The server accepts the token-free choice only for the order's customer, with no other credential posted, after Stripe confirms the bound card is attached and reusable. Authentication failures reported through `decline_code` or `requires_confirmation` are treated the same way. File: core `Services/StripeSubscriptionCompatibility.php`.
Verified live 2026-10-19: T5 (customer 580, no WC token, renewal 68494: only "Visa ending in 3184" offered, 3DS completed on the same PI, one charge, order completed). Cosmetic leftover: an empty bordered box under the card list.
